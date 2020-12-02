<?php

namespace Drupal\gho_general;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the default media resource fetcher service.
 */
class GhoGeneralServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('media.oembed.resource_fetcher');
    $definition->setClass('Drupal\gho_general\OEmbed\GhoResourceFetcher');
  }

}
