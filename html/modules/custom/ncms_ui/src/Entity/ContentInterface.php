<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentInterface extends NodeInterface {

  /**
   * Get the URL for the overview backend listing of this content type.
   *
   * @return \Drupal\Core\Url
   *   A url object.
   */
  public function getOverviewUrl();

  /**
   * Get the bundle label for this content.
   *
   * @return string
   *   The bundle label.
   */
  public function getBundleLabel();

  /**
   * Mark this entity as deleted.
   */
  public function setDeleted();

  /**
   * See if this entity is deleted.
   */
  public function isDeleted();

}
