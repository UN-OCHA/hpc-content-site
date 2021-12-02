<?php

namespace Drupal\gho_graphql\EventSubscriber;

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
    // Add a custom access check to the graphql endpoint to check for a valid
    // access key.
    if ($route = $collection->get('graphql.query.ghi')) {
      $requirements = $route->getRequirements();
      $requirements['_custom_access'] = '\Drupal\gho_graphql\Controller\EndpointAccessController::checkAccess';
      $route->setRequirements($requirements);
    }
  }

}
