<?php

namespace Drupal\ncms_ui\Entity\Storage;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\MediaStorage as BaseMediaStorage;
use Drupal\ncms_ui\Entity\BaseEntityInterface;

/**
 * Defines a custom storage handler class for nodes.
 *
 * This extends the base MediaStorage class, adding required special handling
 * for revisions.
 */
class MediaStorage extends BaseMediaStorage implements ModeratedEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityInterface $entity) {
    return $this->database->query(
      'SELECT [vid] FROM {' . $this->getRevisionTable() . '} WHERE [mid] = :mid ORDER BY [vid]',
      [':mid' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function updateRevisionStatus(BaseEntityInterface $entity, $status, $update_moderation_state = TRUE) {
    if ($entity->isNewRevision()) {
      throw new EntityStorageException("Can't update new revision {$entity->id()}");
    }
    $result = $this->database
      ->update($this->revisionDataTable)
      ->fields((array) [
        'status' => $status,
      ])
      ->condition($this->revisionKey, $entity->getRevisionId())
      ->execute();

    if (empty($result)) {
      return FALSE;
    }

    if ($status == 1 && !$entity->isPublished()) {
      $this->database
        ->update($this->dataTable)
        ->fields((array) [
          'status' => $status,
        ])
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    $last_published = $entity->getLastPublishedRevision();
    if ($status == 0 && !$last_published) {
      $this->database
        ->update($this->dataTable)
        ->fields((array) [
          'status' => $status,
        ])
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    if ($update_moderation_state) {
      if ($status == 0) {
        $entity->set('moderation_state', 'draft');
        $entity->setNewRevision(FALSE);
        $entity->setSyncing(TRUE);
        $entity->save();
      }
      else {
        $entity->set('moderation_state', 'published');
        $entity->setNewRevision(FALSE);
        $entity->setSyncing(TRUE);
        $entity->save();
      }
    }

    $this->resetCache([$entity->id()]);
    return !empty($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    if (!$entity instanceof BaseEntityInterface) {
      return;
    }

    if ($entity->isDeleted()) {
      return;
    }

    if ($entity->isModerationState('trash')) {
      return;
    }

    // For ContentInterface entities, we want to make sure that there is always
    // a meaningful default revision.
    $last_published = $entity->getLastPublishedRevision();
    $latest_revision = $entity->getLatestRevision();
    if ($last_published && $last_published->getRevisionId() != $entity->getRevisionId() && !$last_published->isDefaultRevision()) {
      // Set the last published revision to be the default.
      $last_published->isDefaultRevision(TRUE);
      $last_published->setNewRevision(FALSE);
      $last_published->setSyncing(TRUE);
      $last_published->save();
    }
    elseif (!$last_published && $latest_revision && $latest_revision->getRevisionId() != $entity->getRevisionId() && !$latest_revision->isDefaultRevision()) {
      // If no published revision exists, set the latest revision to be the
      // default.
      $latest_revision->isDefaultRevision(TRUE);
      $latest_revision->setNewRevision(FALSE);
      $latest_revision->setSyncing(TRUE);
      $latest_revision->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLatestRevision(BaseEntityInterface $entity) {
    $revision = $entity->getLatestRevision();

    // First we need to set the previous revision to be the default one.
    $new_default_revision = $revision->getPreviousRevision();
    if (!$new_default_revision) {
      // If there is no previous revision, there is no sense in going further.
      return;
    }

    // Get the last published revision only if the previous revision is not in
    // the trash bin.
    $last_published = !$new_default_revision->isModerationState('trash') ? $revision->getLastPublishedRevision() : NULL;

    // Set default revision based on whether there is a published revision or
    // not.
    $new_default_revision->isDefaultRevision(empty($last_published));
    $new_default_revision->setNewRevision(FALSE);
    $new_default_revision->setSyncing(TRUE);
    $new_default_revision->save();

    if ($last_published) {
      $last_published->isDefaultRevision(TRUE);
      $last_published->setNewRevision(FALSE);
      $last_published->setSyncing(TRUE);
      $last_published->save();
    }

    // Update the content moderation state.
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($new_default_revision);
    if ($content_moderation_state) {
      $content_moderation_state->isDefaultRevision(TRUE);
      $content_moderation_state->setNewRevision(FALSE);
      $content_moderation_state->setSyncing(TRUE);
      ContentModerationState::updateOrCreateFromEntity($content_moderation_state);
    }

    // And then finally delete the latest revision, which is the one that
    // marked the whole entity as being deleted.
    $this->deleteRevision($revision->getRevisionId());
    $this->resetCache([$entity->id()]);
  }

}
