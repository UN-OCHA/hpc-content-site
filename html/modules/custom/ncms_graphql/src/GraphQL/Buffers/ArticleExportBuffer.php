<?php

namespace Drupal\ncms_graphql\GraphQL\Buffers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer as GraphQlEntityBuffer;

/**
 * Collects all exportable articles.
 */
class ArticleExportBuffer extends GraphQlEntityBuffer {

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
   * {@inheritdoc}
   */
  protected function getBufferId($item) {
    return 'node_article';
  }

  /**
   * {@inheritdoc}
   */
  public function resolveBufferArray(array $buffer) {
    $type = 'node';

    // Load the buffered entities.
    $query = $this->entityTypeManager
      ->getStorage($type)
      ->getQuery();
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
