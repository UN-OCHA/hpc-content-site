<?php

namespace Drupal\ncms_ui\Entity\Storage;

use Drupal\Core\Entity\EntityStorageException;
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
   * Saves an entity revision.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentBase $entity
   *   The entity object.
   * @param int $status
   *   The status of the revision.
   *
   * @return int
   *   The revision id.
   */
  public function updateRevisionStatus(ContentBase $entity, $status) {
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
    if ($status == NodeInterface::NOT_PUBLISHED && $last_published) {
      // Revision has been unpublished. Check if there is another published
      // version available that should be set to be the new default revision.
      $last_published->isDefaultRevision(TRUE);
      $last_published->setNewRevision(FALSE);
      $last_published->setSyncing(TRUE);
      $last_published->save();
    }
    if ($status == NodeInterface::NOT_PUBLISHED && !$last_published) {
      $this->database
        ->update($this->dataTable)
        ->fields((array) [
          'status' => $status,
        ])
        ->condition($this->revisionKey, $entity->getRevisionId())
        ->execute();
    }

    $this->resetCache([$entity->id()]);
    return !empty($result);
  }

}
