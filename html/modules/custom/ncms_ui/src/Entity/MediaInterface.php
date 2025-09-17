<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface as BaseMediaInterface;

/**
 * Defines an interface for media entities.
 */
interface MediaInterface extends BaseMediaInterface, BaseEntityInterface {

  /**
   * Check if the media is used in mandatory fields.
   *
   * @return bool
   *   TRUE if the media is used in mandatory fields, FALSE otherwise.
   */
  public function hasMandatoryReferences(): bool;

  /**
   * Check if the media is used in optional fields.
   *
   * @return bool
   *   TRUE if the media is used in optional fields, FALSE otherwise.
   */
  public function hasOptionalReferences(): bool;

  /**
   * Get a list of usage references for this media.
   *
   * @param array|null $entity_type_ids
   *   Optional. If provided, only return references that are instances of one
   *   or more of these types.
   *
   * @return array
   *   An array with the keys 'mandatory' and 'optional'.
   */
  public function getUsageReferences(?array $entity_type_ids = NULL): array;

  /**
   * Get the number of usage references for this media.
   *
   * @param array|null $entity_type_ids
   *   Optional. If provided, only return references that are instances of one
   *   or more of these types.
   *
   * @return int
   *   The total count of usage references.
   */
  public function getUsageCount(?array $entity_type_ids = NULL): int;

  /**
   * Check if the media is required in the context of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check against.
   *
   * @return bool
   *   TRUE if the media is required by the given entity, FALSE otherwise.
   */
  public function isRequiredFor(EntityInterface $entity): bool;

}
