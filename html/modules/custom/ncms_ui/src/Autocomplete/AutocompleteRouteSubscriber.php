<?php

namespace Drupal\ncms_ui\Autocomplete;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subsciber for autocomplete routes.
 */
class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * Alter routes.
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\ncms_ui\Autocomplete\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
