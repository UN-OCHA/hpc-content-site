<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\MediaInterface;

/**
 * Trait to help getting entities from route match.
 */
trait RouteMatchEntityTrait {

  /**
   * Get the current entity from the route match.
   *
   * @return \Drupal\ncms\Entity\MediaInterface|\Drupal\ncms_ui\Entity\ContentInterface|null
   *   The entity or NULL.
   */
  private function getEntityFromRouteMatch() {
    $entity = NULL;
    $route_match = \Drupal::routeMatch();
    if ($entity = $route_match->getParameter('node')) {
      return $entity instanceof ContentInterface ? $entity : NULL;
    }
    elseif ($entity = $route_match->getParameter('media')) {
      return $entity instanceof MediaInterface ? $entity : NULL;
    }
    return NULL;
  }

}
