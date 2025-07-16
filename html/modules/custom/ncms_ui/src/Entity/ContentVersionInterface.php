<?php

namespace Drupal\ncms_ui\Entity;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentVersionInterface {

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
   * Get the moderation state of the entity.
   *
   * @return string
   *   The moderation state string identifier.
   */
  public function getModerationState();

  /**
   * Set the moderation state of the entity.
   *
   * @param string $state
   *   The moderation state string identifier.
   */
  public function setModerationState($state);

  /**
   * Check if the moderation state of the entity has the given state.
   *
   * @param string $state
   *   The moderation state string identifier.
   *
   * @return bool
   *   TRUE if the moderation state is equal to the given one, FALSE otherwise.
   */
  public function isModerationState($state);

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

  /**
   * Get the latest revision.
   *
   * @return \Drupal\ncms_ui\Entity\Content\ContentInterface|null
   *   The latest revision if available.
   */
  public function getLatestRevision();

}
