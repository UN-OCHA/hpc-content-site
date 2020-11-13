<?php

namespace Drupal\gho_access;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the default tree manipulators handler service.
 */
class GhoAccessServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.default_tree_manipulators');
    $definition->setClass('Drupal\gho_access\Menu\GhoMenuLinkTreeManipulators')
      ->addArgument(new Reference('language_manager'));
  }

}
