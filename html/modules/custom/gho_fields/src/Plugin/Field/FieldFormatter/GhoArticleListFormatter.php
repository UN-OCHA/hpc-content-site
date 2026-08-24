<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'gho_article_list' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_article_list",
 *   label = @Translation("GHO article list"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class GhoArticleListFormatter extends FormatterBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $links = [];

    $storage = $this->entityTypeManager->getStorage('node');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $cache = new CacheableMetadata();

    // Retrieve the node links.
    foreach ($items as $item) {
      $url = $item->getUrl();
      if (!$url->isRouted()) {
        continue;
      }
      $parameters = $url->getRouteParameters();
      $entity_type = key($parameters);
      if ($entity_type !== 'node') {
        continue;
      }
      $entity_id = $parameters[$entity_type];
      if (empty($entity_id)) {
        continue;
      }
      $entity_ids[$entity_id] = $url;
    }

    // Generate links for the nodes that are published and accessible.
    if (isset($entity_type) && !empty($entity_ids)) {
      $nodes = $storage->loadMultiple(array_keys($entity_ids));

      foreach ($entity_ids as $id => $url) {
        if (isset($nodes[$id])) {
          $node = $nodes[$id];
          $attributes = new Attribute();

          if ($node->hasTranslation($langcode)) {
            $node = $node->getTranslation($langcode);
          }
          else {
            $attributes->addClass('node--untranslated');
          }

          if (!$node->isPublished()) {
            $attributes->addClass('node--unpublished');
          }

          $access = $node->access('view', $this->currentUser, TRUE);

          if ($access->isAllowed()) {
            $links[] = [
              'url' => $url,
              'title' => $node->title->value,
              'attributes' => $attributes,
            ];
          }

          $cache = $cache->merge(CacheableMetadata::createFromObject($access));
        }
      }
    }

    if (!empty($links)) {
      $element = [
        '#theme' => 'gho_article_list_formatter',
        '#links' => $links,
      ];
      $cache->applyTo($element);
    }

    return $element;
  }

}
