<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('current_user')
    );
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
