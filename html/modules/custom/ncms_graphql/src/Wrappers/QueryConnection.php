<?php

namespace Drupal\ncms_graphql\Wrappers;

use Drupal\Core\Entity\Query\QueryInterface;
use GraphQL\Deferred;

/**
 * Helper class that wraps entity queries.
 */
class QueryConnection {

  /**
   * The query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * QueryConnection constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query object.
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }

  /**
   * Return the number of results.
   *
   * @return int
   *   The number of results.
   */
  public function count() {
    $query = clone $this->query;
    $query->range(NULL, NULL)->count();
    /** @var int */
    return $query->execute();
  }

  /**
   * Return the ids.
   *
   * @return int[]
   *   An array of ids.
   */
  public function ids() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }
    return array_values($result);
  }

  /**
   * Return all items.
   *
   * @return array|\GraphQL\Deferred
   *   The promise.
   */
  public function items() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $buffer = \Drupal::service('graphql.buffer.entity');
    $callback = $buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(function () use ($callback) {
      return $callback();
    });
  }

}
