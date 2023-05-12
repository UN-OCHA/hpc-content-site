<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentVersionInterface extends NodeInterface {

  /**
   * Get the version id.
   *
   * This is an auto-calculated number based on the number of revisions of a
   * node.
   *
   * @return int
   *   The internal version number.
   */
  public function getVersionId();

  /**
   * Get the current moderation state label.
   *
   * @return string
   *   The label of the current moderation status.
   */
  public function getModerationStateLabel();

  /**
   * Get the version status.
   *
   * @return string
   *   The version status.
   */
  public function getVersionStatus();

  /**
   * Get the latest published revision.
   *
   * @return \Drupal\ncms_ui\Entity\ContentVersionInterface|null
   *   The latest published revision if available.
   */
  public function getLastPublishedRevision();

}
