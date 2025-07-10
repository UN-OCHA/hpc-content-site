<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Entity\EntitySoftDeleteInterface;
use Drupal\ncms_ui\Entity\MediaInterface;

/**
 * Trait for moderated entities.
 */
trait ModeratedEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function getVersionId() {
    $storage = $this->getEntityStorage();
    $revision_ids = $storage->revisionIds($this);
    $version_key = array_search($this->getRevisionId(), $revision_ids);
    return $version_key !== FALSE ? $version_key + 1 : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationState() {
    return $this->moderation_state?->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setModerationState($state) {
    $this->moderation_state->value = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function isModerationState($state) {
    return $this->getModerationState() == $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationStateLabel() {
    /** @var \Drupal\content_moderation\ModerationInformation $moderation_information */
    $moderation_information = \Drupal::service('content_moderation.moderation_information');
    return $moderation_information->getOriginalState($this)->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentStatus() {
    if ($this->isDeleted()) {
      return ContentVersionInterface::CONTENT_STATUS_DELETED;
    }
    $latest_revision = $this->getLatestRevision();
    if ($latest_revision instanceof EntityPublishedInterface && $latest_revision->isPublished()) {
      return ContentVersionInterface::CONTENT_STATUS_PUBLISHED;
    }

    $storage = $this->getEntityStorage();
    $revision_ids = $storage->revisionIds($this);
    $revisions = $storage->loadMultipleRevisions($revision_ids);

    $count_published = count(array_filter($revisions, function (EntityInterface $revision) {
      return $revision instanceof EntityPublishedInterface && $revision->isPublished();
    }));
    // If there are no published versions we call it "Draft". If at least one
    // published version exists we call it "Published with newer draft".
    return !$count_published ? ContentVersionInterface::CONTENT_STATUS_DRAFT : ContentVersionInterface::CONTENT_STATUS_PUBLISHED_WITH_DRAFT;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentStatusLabel() {
    $content_status = $this->getContentStatus();
    $label_map = [
      ContentVersionInterface::CONTENT_STATUS_PUBLISHED => $this->t('Published'),
      ContentVersionInterface::CONTENT_STATUS_PUBLISHED_WITH_DRAFT => $this->t('Published with newer draft'),
      ContentVersionInterface::CONTENT_STATUS_DRAFT => $this->t('Draft'),
      ContentVersionInterface::CONTENT_STATUS_DELETED => $this->t('Deleted'),
    ];
    return $label_map[$content_status];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersionStatusLabel() {
    $latest_revision = $this->getLatestRevision();
    if ($latest_revision instanceof EntitySoftDeleteInterface && $latest_revision->isDeleted()) {
      return $this->t('Deleted');
    }
    if ($this->isPublished()) {
      return $this->t('Published');
    }
    return $this->isLatestRevision() ? $this->t('Draft') : $this->t('Archived');
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision() {
    $storage = $this->getEntityStorage();
    $revision_id = $storage->getLatestRevisionId($this->id());
    return $revision_id ? $storage->loadRevision($revision_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPublishedRevision() {
    $storage = $this->getEntityStorage();
    $revision_ids = array_reverse($storage->revisionIds($this));
    $revisions = $storage->loadMultipleRevisions($revision_ids);
    foreach ($revisions as $revision) {
      if (!$revision instanceof EntityPublishedInterface || !$revision->isPublished()) {
        continue;
      }
      return $revision;
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousRevision() {
    $storage = $this->getEntityStorage();
    $revision_ids = array_reverse($storage->revisionIds($this));
    if (count($revision_ids) < 2) {
      return NULL;
    }
    array_shift($revision_ids);
    $previous_revision_id = array_shift($revision_ids);

    return $previous_revision_id ? $storage->loadRevision($previous_revision_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStorage() {
    if ($this instanceof ContentInterface) {
      return $this->entityTypeManager()->getStorage('node');
    }
    if ($this instanceof MediaInterface) {
      return $this->entityTypeManager()->getStorage('media');
    }

  }

}
