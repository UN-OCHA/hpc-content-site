<?php

namespace Drupal\ncms_ui\Entity\Storage;

use Drupal\ncms_ui\Entity\BaseEntityInterface;

/**
 * Defines an entity storage interface for entities that can be moderated.
 */
interface ModeratedEntityStorageInterface {

  /**
   * Update the status for an entity revision.
   *
   * @param \Drupal\ncms_ui\Entity\BaseEntityInterface $entity
   *   The entity object.
   * @param int $status
   *   The status of the revision.
   * @param bool $update_moderation_state
   *   Whether to also update the moderation state.
   *
   * @return int
   *   The revision id.
   */
  public function updateRevisionStatus(BaseEntityInterface $entity, $status, $update_moderation_state = TRUE);

  /**
   * Safely delete the latest revision.
   *
   * This will take care of setting new default revisions, and also update
   * related content moderation entities.
   *
   * @param \Drupal\ncms_ui\Entity\BaseEntityInterface $entity
   *   The entity for which the latest revision should be deleted.
   */
  public function deleteLatestRevision(BaseEntityInterface $entity);

}
