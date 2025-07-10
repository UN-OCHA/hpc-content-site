<?php

namespace Drupal\ncms_ui\Entity\Storage;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\ncms_ui\Entity\BaseEntityInterface;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorage;

/**
 * Defines a custom storage handler class for nodes.
 *
 * This extends the base NodeStorage class, adding required special handling
 * for revisions.
 */
class ContentStorage extends NodeStorage implements ModeratedEntityStorageInterface {

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

    if ($status == NodeInterface::PUBLISHED && !$entity->isPublished()) {
      $this->database
        ->update($this->dataTable)
        ->fields((array) [
          'status' => $status,
        ])
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    $last_published = $entity->getLastPublishedRevision();
    if ($status == NodeInterface::NOT_PUBLISHED && !$last_published) {
      $this->database
        ->update($this->dataTable)
        ->fields((array) [
          'status' => $status,
        ])
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    if ($update_moderation_state) {
      if ($status == NodeInterface::NOT_PUBLISHED) {
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

    if (!$entity instanceof ContentInterface || $entity->isDeleted()) {
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
  protected function hasFieldValueChanged(FieldDefinitionInterface $field_definition, ContentEntityInterface $entity, ContentEntityInterface $original) {
    // Work around an issue where field data of content with active
    // translations sometimes doesn't save correctly when using the
    // "Publish as correction" or "Publish as revision" submit buttons on the
    // node edit form.
    if ($entity instanceof ContentInterface && $entity->getTranslationLanguages(FALSE)) {
      // Always return TRUE if the content has translations. The reason is that
      // hasFieldValueChanged() doesn't fetch the previous revisions field
      // values and thus falsely reports the fields to not have changed,
      // preventing the changes from beeing written to storage. The main issue
      // is probably somewhere else, but returning TRUE here seems to fix the
      // issue without further side effects.
      return TRUE;
    }
    return parent::hasFieldValueChanged($field_definition, $entity, $original);
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
