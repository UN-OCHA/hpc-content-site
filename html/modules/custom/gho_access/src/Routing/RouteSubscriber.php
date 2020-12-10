<?php

namespace Drupal\gho_access\Routing;

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
    // Add a requirement to check the global language visibility for the
    // GHO site, as first requirement.
    if ($route = $collection->get('entity.node.canonical')) {
      $requirements = ['_access_check_gho_language_visibility' => '{node}'];
      $requirements += $route->getRequirements();
      $route->setRequirements($requirements);
    }

    // Ensure all the admin content pages are considered as admin routes as this
    // cannot be defined in the views UI...
    // @see https://www.drupal.org/project/drupal/issues/2719797
    foreach ($collection->all() as $route) {
      if (strpos($route->getPath(), '/admin/content') === 0) {
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
