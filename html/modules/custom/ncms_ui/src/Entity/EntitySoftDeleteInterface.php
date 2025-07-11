<?php

namespace Drupal\ncms_ui\Entity;

/**
 * Defines an interface entities that can be soft deleted (moved to trash).
 */
interface EntitySoftDeleteInterface {

  /**
   * Mark this entity as deleted.
   */
  public function setDeleted();

  /**
   * See if this entity is deleted.
   */
  public function isDeleted();

}
