<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'gho_menu' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_menu",
 *   label = @Translation("GHO menu formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GhoMenuFormatter extends EntityReferenceFormatterBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Meny link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkTree = $menu_link_tree;
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
      $container->get('menu.link_tree'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [
      '#type' => 'container',
    ];

    // @todo use a formatter setting for that instead of hardcoding it.
    $view_mode = 'related_article';

    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $storage = $this->entityTypeManager->getStorage('node');

    foreach ($this->getEntitiesToView($items, $langcode) as $entity) {
      $plugin_id = $entity->getPluginId();

      $parameters = new MenuTreeParameters();
      // This will fetch the tree starting from the menu link.
      $parameters->setRoot($plugin_id);
      // Only get the direct children.
      $parameters->setMaxdepth(1);

      // Load tree.
      $tree = $this->menuLinkTree->load('main', $parameters);

      // Check the access to the nodes in the menu and ensure they are sorted.
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $tree = reset($tree);

      // Get the nodes associated with the links and their render array.
      if (!empty($tree->subtree)) {
        foreach ($tree->subtree as $item) {
          $url = $item->link->getUrlObject();
          if ($url->isRouted()) {
            $parameters = $url->getRouteParameters();
            $entity_type = key($parameters);
            if ($entity_type === 'node') {
              $node = $storage->load($parameters[$entity_type]);
              $element[] = $view_builder->view($node, $view_mode);
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Limit formatter to only menu entity types.
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'menu_link_content');
  }

}
