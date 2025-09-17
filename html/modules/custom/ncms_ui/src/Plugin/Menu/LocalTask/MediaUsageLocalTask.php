<?php

namespace Drupal\ncms_ui\Plugin\Menu\LocalTask;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Entity\Media\MediaBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom local tasks for the media usage tabs.
 */
class MediaUsageLocalTask extends LocalTaskDefault implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->routeMatch = $container->get('current_route_match');
    $request = $container->get('request_stack')->getCurrentRequest();

    // If this is the local task for the main "Places used" tab, see if there
    // are actually some counts, and if there are no counts for the content
    // subtask but counts for the paragraphs, then change it to point at that
    // instead.
    if ($instance->getPluginId() == 'ncms_ui:media_usage') {
      $media = $instance->getMediaEntityFromRequest($request);
      $content_usage = $media->getUsageCount(['node']);
      $paragraph_usage = $media->getUsageCount(['paragraph']);
      if (!$content_usage && $paragraph_usage) {
        $instance->pluginDefinition['route_name'] = 'view.media_usage.page_paragraphs';
      }
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(?Request $request = NULL) {
    $media = $this->getMediaEntityFromRequest($request);
    if (!$media instanceof MediaBase) {
      return parent::getTitle();
    }
    $type = match($this->getRouteName()) {
      'view.media_usage.page_content' => 'node',
      'view.media_usage.page_paragraphs' => 'paragraph',
    };
    $usage_count = $media->getUsageCount([$type]);
    return parent::getTitle() . ' (' . $usage_count . ')';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $route_paramaters = $this->routeMatch->getRawParameters();
    $cache_tags = [
      'media:' . $route_paramaters->get('media'),
      'media_usage:' . $this->getRouteName(),
    ];
    return Cache::mergeTags(parent::getCacheTags(), $cache_tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = [
      'url.path',
      'route.name',
    ];
    return Cache::mergeTags(parent::getCacheContexts(), $cache_contexts);
  }

  /**
   * Get the media entity from a request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request|null $request
   *   The request object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   A media entity or NULL
   */
  private function getMediaEntityFromRequest(?Request $request = NULL) {
    $media = $request?->get('media');
    return $media !== NULL && !is_object($media) ? $this->entityTypeManager->getStorage('media')->load($media) : $media;
  }

}
