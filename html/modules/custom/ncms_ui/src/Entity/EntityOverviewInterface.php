<?php

namespace Drupal\ncms_ui\Entity;

/**
 * Defines an interface for entities with content spaces.
 */
interface EntityOverviewInterface {

  /**
   * Return the URL of the overview page.
   *
   * @return \Drupal\Core\Url
   *   The url of the verview page.
   */
  public function getOverviewUrl();

}
