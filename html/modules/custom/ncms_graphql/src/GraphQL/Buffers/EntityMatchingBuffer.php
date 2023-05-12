<?php

namespace Drupal\ncms_graphql\GraphQL\Buffers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer as GraphQlEntityBuffer;

/**
 * Collects entity titles per type and matches them all at once in the end.
 */
class EntityMatchingBuffer extends GraphQlEntityBuffer {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityBuffer constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Add an item to the buffer.
   *
   * @param string $type
   *   The entity type of the given entity ids.
   * @param array|string $title
   *   The entity titles to match for.
   *
   * @return \Closure
   *   The callback to invoke to load the result for this buffer item.
   */
  public function addTitleString($type, $title) {
    $item = new \ArrayObject([
      'type' => $type,
      'title' => $title,
    ]);

    return $this->createBufferResolver($item);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBufferId($item) {
    return $item['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function resolveBufferArray(array $buffer) {
    $type = reset($buffer)['type'];
    $titles = array_map(function (\ArrayObject $item) {
      return $item['title'];
    }, $buffer);

    $titles = array_values(array_unique($titles));

    // Load the buffered entities.
    $query = $this->entityTypeManager
      ->getStorage($type)
      ->getQuery();

    $title_match_group = $query->orConditionGroup();
    foreach ($titles as $title) {
      $title_match_group->condition('title', '%' . $title . '%', 'LIKE');
    }
    $query->condition($title_match_group);
    $query->accessCheck(TRUE);
    $entity_ids = $query->execute();
    $entities = $entity_ids ? $this->entityTypeManager
      ->getStorage($type)
      ->loadMultiple($entity_ids) : [];

    return array_map(function ($item) use ($entities) {
      return array_filter($entities, function ($entity) use ($item) {
        return strpos(strtolower($entity->label()), strtolower($item['title'])) !== FALSE;
      });
    }, $buffer);
  }

}
