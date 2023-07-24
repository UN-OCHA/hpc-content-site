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
      // Instead denying all access, we set a custom access callback that can
      // be a bit more fine-grained, even though at the moment it only checks
      // if the user has edit rights.
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\ViewController::nodeCanonicalRouteAccess');
    }
    // Add dynamic titles to the "Add term" local actions.
    if ($route = $collection->get('entity.taxonomy_term.add_form')) {
      $route->setDefault('_title_callback', '\Drupal\ncms_ui\Controller\TermController::addFormTitle');
    }
    // Allow access to the revisions tab only if the user can also revert
    // revisions.
    if ($route = $collection->get('entity.node.version_history')) {
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\ContentController::versionAccess');
    }
    if ($route = $collection->get('node.add')) {
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\ContentController::nodeCreateAccess');
    }
    if ($route = $collection->get('node.add_page')) {
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\ContentController::nodeCreateAccess');
    }
    if ($route = $collection->get('entity.media.add_form')) {
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\MediaController::mediaCreateAccess');
    }
    if ($route = $collection->get('entity.media.add_page')) {
      $route->setRequirement('_custom_access', '\Drupal\ncms_ui\Controller\MediaController::mediaCreateAccess');
    }
    // Allow access to the media collection.
    if ($route = $collection->get('entity.media.collection')) {
      $requirements = $route->getRequirements();
      $requirements['_permission'] = 'access media overview';
      $route->setRequirements($requirements);
    }
  }

}
