<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\Core\Entity\EntityStorageException;
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
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   * @param int $status
   *   The status of the revision.
   *
   * @return int
   *   The revision id.
   */
  public function updateRevisionStatus(NodeInterface $entity, $status) {
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
    return !empty($result);
  }

}
