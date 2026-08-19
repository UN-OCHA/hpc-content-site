<?php

namespace Drupal\ncms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\Storage\ContentStorage;
use Drupal\node\NodeInterface;

/**
 * Coordinates editorial revision transitions for content entities.
 */
class ContentRevisionWorkflow {

  const RESULT_CREATED_PUBLISHED_CORRECTION = 'created_published_correction';
  const RESULT_CREATED_PUBLISHED_VERSION = 'created_published_version';
  const RESULT_NO_CHANGES = 'no_changes';
  const RESULT_NO_ACTION = 'no_action';
  const RESULT_PUBLISHED_CURRENT_VERSION = 'published_current_version';
  const RESULT_SAVED_DRAFT = 'saved_draft';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Saves or publishes a draft entity.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $updated_entity
   *   The entity built from the submitted form.
   * @param \Drupal\ncms_ui\Entity\ContentInterface $original_entity
   *   The entity as it was loaded before form submission.
   * @param bool $entity_updated
   *   Whether the submitted entity changed.
   *
   * @return string
   *   One of the RESULT_* constants.
   */
  public function saveAndPublish(ContentInterface $updated_entity, ContentInterface $original_entity, bool $entity_updated): string {
    if ($entity_updated) {
      $this->savePublishedRevision($updated_entity);
      return self::RESULT_CREATED_PUBLISHED_VERSION;
    }

    $this->publishExistingRevision($original_entity);
    return self::RESULT_PUBLISHED_CURRENT_VERSION;
  }

  /**
   * Publishes changes as a correction to the current published version.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $updated_entity
   *   The entity built from the submitted form.
   * @param \Drupal\ncms_ui\Entity\ContentInterface $original_entity
   *   The entity as it was loaded before form submission.
   * @param bool $entity_updated
   *   Whether the submitted entity changed.
   *
   * @return string
   *   One of the RESULT_* constants.
   */
  public function publishCorrection(ContentInterface $updated_entity, ContentInterface $original_entity, bool $entity_updated): string {
    if (!$entity_updated) {
      if (!$updated_entity->isPublished()) {
        $this->publishExistingRevision($original_entity);
        return self::RESULT_PUBLISHED_CURRENT_VERSION;
      }
      return self::RESULT_NO_ACTION;
    }

    $last_published_revision_id = $updated_entity->getLastPublishedRevision()?->getRevisionId();
    $this->savePublishedRevision($updated_entity);

    if ($last_published_revision_id && $last_published_revision_id != $updated_entity->getRevisionId()) {
      $last_published = $this->getContentStorage()->loadRevision($last_published_revision_id);
      if ($last_published instanceof ContentInterface) {
        $this->unpublishExistingRevision($last_published);
        return self::RESULT_CREATED_PUBLISHED_CORRECTION;
      }
    }

    return self::RESULT_CREATED_PUBLISHED_VERSION;
  }

  /**
   * Publishes changes as a new public revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $updated_entity
   *   The entity built from the submitted form.
   * @param \Drupal\ncms_ui\Entity\ContentInterface $original_entity
   *   The entity as it was loaded before form submission.
   * @param bool $entity_updated
   *   Whether the submitted entity changed.
   *
   * @return string
   *   One of the RESULT_* constants.
   */
  public function publishRevision(ContentInterface $updated_entity, ContentInterface $original_entity, bool $entity_updated): string {
    if ($entity_updated) {
      $this->savePublishedRevision($updated_entity);
      return self::RESULT_CREATED_PUBLISHED_VERSION;
    }

    if (!$updated_entity->isPublished()) {
      $this->publishExistingRevision($original_entity);
      return self::RESULT_PUBLISHED_CURRENT_VERSION;
    }

    return self::RESULT_NO_ACTION;
  }

  /**
   * Saves the updated entity as a draft revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $updated_entity
   *   The entity built from the submitted form.
   * @param bool $entity_updated
   *   Whether the submitted entity changed.
   *
   * @return string
   *   One of the RESULT_* constants.
   */
  public function saveDraft(ContentInterface $updated_entity, bool $entity_updated): string {
    if (!$entity_updated) {
      return self::RESULT_NO_CHANGES;
    }

    $updated_entity->setUnpublished();
    $updated_entity->save();
    return self::RESULT_SAVED_DRAFT;
  }

  /**
   * Publishes an existing revision without creating a new revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The entity revision.
   *
   * @return bool
   *   TRUE if the revision was updated.
   */
  public function publishExistingRevision(ContentInterface $entity): bool {
    return $this->updateExistingRevisionStatus($entity, NodeInterface::PUBLISHED);
  }

  /**
   * Unpublishes an existing revision without creating a new revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The entity revision.
   *
   * @return bool
   *   TRUE if the revision was updated.
   */
  public function unpublishExistingRevision(ContentInterface $entity): bool {
    return $this->updateExistingRevisionStatus($entity, NodeInterface::NOT_PUBLISHED);
  }

  /**
   * Saves the entity as a published revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The entity to save.
   */
  private function savePublishedRevision(ContentInterface $entity): void {
    $entity->setPublished();
    $entity->save();
  }

  /**
   * Updates the published status for an existing revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The entity revision.
   * @param int $status
   *   The published status.
   *
   * @return bool
   *   TRUE if the revision was updated.
   */
  private function updateExistingRevisionStatus(ContentInterface $entity, int $status): bool {
    return (bool) $this->getContentStorage()->updateRevisionStatus($entity, $status);
  }

  /**
   * Gets the custom content storage handler.
   *
   * @return \Drupal\ncms_ui\Entity\Storage\ContentStorage
   *   The node content storage handler.
   */
  private function getContentStorage(): ContentStorage {
    $content_storage = $this->entityTypeManager->getStorage('node');
    if (!$content_storage instanceof ContentStorage) {
      throw new \LogicException('The node storage handler must be ContentStorage for content revision workflow operations.');
    }
    return $content_storage;
  }

}
