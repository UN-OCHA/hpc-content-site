<?php

namespace Drupal\gho_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic Menu block.
 *
 * @Block(
 *   id = "gho_main_menu",
 *   admin_label = @Translation("GHO main menu"),
 *   category = @Translation("Menus"),
 * )
 */
class GhoMainMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MenuLinkTreeInterface $menu_tree, MenuActiveTrailInterface $menu_active_trail = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->menuTree = $menu_tree;
    $this->menuActiveTrail = $menu_active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $menu_name = 'main';

    // Adjust the menu tree parameters based on the block's configuration.
    $parameters = $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth(1);
    $parameters->setMaxDepth(2);

    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    return $this->menuTree->build($tree);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\system\Plugin\Block\SystemMenuBlock::getCacheTags()
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $cache_tags[] = 'config:system.menu.main';
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\system\Plugin\Block\SystemMenuBlock::getCacheContexts()
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route.menu_active_trails:main']);
  }

}
