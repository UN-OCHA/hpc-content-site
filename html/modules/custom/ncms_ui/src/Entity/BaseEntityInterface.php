<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Defines a shared base interface for content and media entities.
 */
interface BaseEntityInterface extends ContentSpaceAwareInterface, ContentVersionInterface, EntitySoftDeleteInterface, EntityOverviewInterface, EntityPublishedInterface, ContentEntityInterface {

  /**
   * Get the bundle label for this content.
   *
   * @return string
   *   The bundle label.
   */
  public function getBundleLabel();

  /**
   * Retrieve entity operations specific to our workflows.
   *
   * @return array
   *   An array of operation links.
   */
  public function getEntityOperations();

  /**
   * Get the entity storage.
   *
   * @return \Drupal\ncms_ui\Entity\Storage\ContentStorage|\Drupal\ncms_ui\Entity\Storage\MediaStorage|null
   *   The storage object.
   */
  public function getEntityStorage();

}
