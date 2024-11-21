<?php

namespace Drupal\ncms_graphql\Wrappers;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\ncms_graphql\ResultWrapperInterface;

/**
 * Helper class that wraps entity queries.
 */
class ContentSearchWrapper implements ResultWrapperInterface {

  /**
   * An array of entitie objects.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[]
   */
  protected $entities;

  /**
   * ContentSearchWrapper constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   The query object.
   */
  public function __construct(array $entities) {
    $this->entities = $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->entities);
  }

  /**
   * {@inheritdoc}
   */
  public function ids() {
    return array_keys($this->entities);
  }

  /**
   * {@inheritdoc}
   */
  public function metaData() {
    return array_map(function (ContentEntityInterface $entity) {
      return (object) [
        'id' => $entity->id(),
        'title' => $entity->label(),
        'title_short' => $entity->hasField('field_short_title') ? $entity->get('field_short_title')->value : NULL,
        'summary' => $entity->hasField('field_summary') ? $entity->get('field_summary')->value : NULL,
        'status' => $entity instanceof EntityPublishedInterface ? $entity->isPublished() : 0,
        'created' => method_exists($entity, 'getCreatedTime') ? (new \DateTime())->setTimestamp($entity->getCreatedTime())->format(\DateTime::ISO8601) : NULL,
        'updated' => $entity instanceof EntityChangedInterface ? (new \DateTime())->setTimestamp($entity->getChangedTime())->format(\DateTime::ISO8601) : NULL,
        'autoVisible' => $entity->hasField('field_automatically_visible') ? $entity->get('field_automatically_visible')->value : NULL,
        'forceUpdate' => $entity->get('force_update')?->value,
      ];
    }, $this->entities);
  }

  /**
   * {@inheritdoc}
   */
  public function items() {
    return $this->entities;
  }

}
