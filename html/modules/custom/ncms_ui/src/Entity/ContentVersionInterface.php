<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentVersionInterface extends NodeInterface {

  const CONTENT_STATUS_PUBLISHED = 'published';
  const CONTENT_STATUS_PUBLISHED_WITH_DRAFT = 'published_with_draft';
  const CONTENT_STATUS_DRAFT = 'draft';
  const CONTENT_STATUS_DELETED = 'trash';

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
   * Get the status of the content entity.
   *
   * @return string
   *   The status. See the CONTENT_STATUS_* constants.
   */
  public function getContentStatus();

  /**
   * Get the status label of the content entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status.
   */
  public function getContentStatusLabel();

  /**
   * Get the status of the revision.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status.
   */
  public function getVersionStatusLabel();

  /**
   * Get the latest published revision.
   *
   * @return \Drupal\ncms_ui\Entity\ContentVersionInterface|null
   *   The latest published revision if available.
   */
  public function getLastPublishedRevision();

  /**
   * Get the previous revision.
   *
   * @return \Drupal\ncms_ui\Entity\ContentVersionInterface|null
   *   The previous revision if available.
   */
  public function getPreviousRevision();

}
