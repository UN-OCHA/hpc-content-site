<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Plugin implementation of the 'gho_related_articles' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_related_articles",
 *   label = @Translation("GHO related articles formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class GhoRelatedArticlesFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [
      '#theme' => 'gho_related_articles_formatter',
      '#list' => [],
    ];

    // Get the render arrays of the nodes associated with the direct
    // children of the selected menu entry.
    foreach ($items as $item) {
      $list = static::getNodeListFromMenu($item->entity->getPluginId());
      $element['#list'] = array_merge($element['#list'], $list);
    }

    return $element;
  }

  /**
   * Get a list of node render arrays for the view mode from a menu entry.
   *
   * @param string $menu_id
   *   Id of the menu link from which to retrieve the children menu entries and
   *   their associated nodes.
   * @param array $exclude
   *   Nodes to exclude for the returned list.
   *
   * @return array
   *   List of node build arrays for the view mode.
   */
  public static function getNodeListFromMenu($menu_id, array $exclude = []) {
    $list = [];

    $menu_tree = \Drupal::service('menu.link_tree');
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $storage = $entity_type_manager->getStorage('node');
    $view_builder = $entity_type_manager->getViewBuilder('node');

    // Parameter to load the menu children of the given menu.
    $parameters = new MenuTreeParameters();
    // This will fetch the tree starting from the menu link.
    $parameters->setRoot($menu_id);
    // Only get the direct children.
    $parameters->setMaxdepth(1);

    // Load tree.
    $tree = $menu_tree->load('main', $parameters);

    // Check the access to the nodes in the menu and ensure they are sorted.
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    // Tree is an associated array with a key for the parent menu entry
    // and a \Drupal\Core\Menu\MenuLinkTreeElement[] as value.
    $tree = $menu_tree->transform($tree, $manipulators);
    $tree = reset($tree);

    // Get the nodes associated with the links and their render array.
    if (isset($tree, $tree->subtree)) {
      foreach ($tree->subtree as $item) {
        $url = $item->link->getUrlObject();
        if ($url->isRouted()) {
          $parameters = $url->getRouteParameters();
          $entity_type = key($parameters);
          $entity_id = $parameters[$entity_type];

          // Load the node associated with the menu link and it's render
          // array for the view mode to the list.
          if ($entity_type === 'node' && !in_array($entity_id, $exclude)) {
            $node = $storage->load($entity_id);
            $list[] = $view_builder->view($node, 'related_article');
          }
        }
      }
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // Limit formatter to only menu entity types.
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'menu_link_content');
  }

}
