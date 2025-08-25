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
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    $media_id = $request->get('media');
    $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    if (!$media instanceof MediaBase) {
      return parent::getTitle();
    }
    $type = match($this->getRouteName()) {
      'view.media_usage.page_content' => 'node',
      'view.media_usage.page_paragraphs' => 'paragraph',
    };
    $references = $media->getUsageReferences([$type]);
    return parent::getTitle() . ' (' . (count($references['mandatory']) + count($references['optional'])) . ')';
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

}
