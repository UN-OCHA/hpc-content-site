<?php

namespace Drupal\ncms_ui\Entity\Storage;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorage;

/**
 * Defines a custom storage handler class for nodes.
 *
 * This extends the base NodeStorage class, adding required special handling
 * for revisions.
 */
class ContentStorage extends NodeStorage {

  /**
   * Update the status for an entity revision.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentBase $entity
   *   The entity object.
   * @param int $status
   *   The status of the revision.
   * @param bool $update_moderation_state
   *   Whether to also update the moderation state.
   *
   * @return int
   *   The revision id.
   */
  public function updateRevisionStatus(ContentBase $entity, $status, $update_moderation_state = TRUE) {
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

    if (!$entity instanceof ContentBase || $entity->isDeleted()) {
      return;
    }

    // For entities of type ContentBase, we want to make sure that there is
    // always a meaningful default revision.
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
    if ($entity instanceof ContentBase && $entity->getTranslationLanguages(FALSE)) {
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

}
