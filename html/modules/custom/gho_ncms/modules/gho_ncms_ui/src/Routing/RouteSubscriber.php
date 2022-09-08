<?php

namespace Drupal\gho_ncms_ui\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Deny access to the default node view.
    if ($route = $collection->get('entity.node.canonical')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
