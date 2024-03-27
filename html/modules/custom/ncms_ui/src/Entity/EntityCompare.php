<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Service class for some low-tech entity comparison.
 */
class EntityCompare {

  /**
   * Compare 2 entities and see if they have changed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $updated_entity
   *   The updated entity.
   * @param \Drupal\Core\Entity\ContentEntityInterface $original_entity
   *   The original entity.
   *
   * @return bool
   *   TRUE if the entities have changed, FALSE otherwise.
   */
  public function hasChanged(ContentEntityInterface $updated_entity, ContentEntityInterface $original_entity) {
    return $this->hashEntity($updated_entity) != $this->hashEntity($original_entity);
  }

  /**
   * Calculate a hash for the current state of the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object to calculate the hash for.
   *
   * @return string
   *   An md5 hash.
   */
  private function hashEntity(ContentEntityInterface $entity) {
    $entity_data = $this->buildHashableEntityData($entity);
    return md5(str_replace(['"', "\n"], '', json_encode($entity_data)));
  }

  /**
   * Build an array with the entity data that will be used to generate a hash.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   An array representing the hashable part of the entity.
   */
  private function buildHashableEntityData(ContentEntityInterface $entity) {
    $entity_data = $entity->toArray();
    if ($entity instanceof Paragraph) {
      $entity_data += $entity->getAllBehaviorSettings() ?? [];
    }
    foreach ($entity_data as $field_name => &$field) {
      if (empty($field) || strpos($field_name, 'field_') !== 0) {
        continue;
      }
      $field_list = $entity->get($field_name);
      if (!$field_list instanceof EntityReferenceFieldItemList) {
        continue;
      }
      $entities = $field_list->referencedEntities();
      foreach ($entities as $delta => $_entity) {
        $field[$delta]['hash'] = self::hashEntity($_entity);
        unset($field[$delta]['entity']);
      }
    }
    $remove_keys = [
      'vid',
      'changed',
      'revision_id',
      'revision_timestamp',
      'revision_uid',
      'revision_translation_affected',
      'comment',
      'path',
    ];
    self::reduceArray($entity_data, $remove_keys);
    return $entity_data;
  }

  /**
   * Reduce an array by removing empty items.
   *
   * @param array $array
   *   The input array.
   * @param array $remove_keys
   *   The array with keys to remove.
   */
  private static function reduceArray(array &$array, array $remove_keys = []) {
    foreach ($array as $key => &$a) {
      if (!empty($remove_keys) && in_array($key, $remove_keys)) {
        unset($array[$key]);
        continue;
      }
      if (is_array($a)) {
        if (empty($a)) {
          unset($array[$key]);
        }
        else {
          self::reduceArray($a, $remove_keys);
        }
      }
      if (is_object($a)) {
        if ($a instanceof ContentEntityInterface) {
          $array[$key] = $a->toArray();
          self::reduceArray($array[$key], $remove_keys);
        }
        else {
          unset($array[$key]);
        }
      }
      if (is_bool($a)) {
        $a = $a ? 1 : 0;
      }
    }
    $array = array_filter($array);
    ksort($array);
  }

}
