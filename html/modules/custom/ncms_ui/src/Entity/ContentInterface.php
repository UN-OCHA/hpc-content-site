<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentInterface extends NodeInterface, ContentSpaceAwareInterface, ContentVersionInterface, EntityOverviewInterface, IframeDisplayContentInterface {

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
   * Check if the content has any tags.
   *
   * @return bool
   *   TRUE if it can be published, FALSE otherwise.
   */
  public function hasTags();

  /**
   * Mark this entity as deleted.
   */
  public function setDeleted();

  /**
   * See if this entity is deleted.
   */
  public function isDeleted();

  /**
   * Retrieve entity operations specific to our workflows.
   *
   * @return array
   *   An array of operation links.
   */
  public function getEntityOperations();

  /**
   * Build the metadata used for diff displays.
   *
   * @return array
   *   A render array.
   */
  public function buildMetaDataForDiff();

}
