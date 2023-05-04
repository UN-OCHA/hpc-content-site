<?php

namespace Drupal\ncms_ui\Routing;

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
      // Instead denying all access, we cet a custom access callback that can
      // be a bit more fine-grained, even though at the moment it only checks
      // if the user is logged-in.
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\ViewController::nodeCanonicalRouteAccess');
    }
    // Add dynamic titles to the "Add term" local actions.
    if ($route = $collection->get('entity.taxonomy_term.add_form')) {
      $route->setDefault('_title_callback', '\Drupal\ncms_ui\Controller\TermController::addFormTitle');
    }
    // Allow access to the revisions tab only if the user can also revert
    // revisions.
    if ($route = $collection->get('entity.node.version_history')) {
      $route->setRequirement('_entity_access', 'node.update');
    }
  }

}
