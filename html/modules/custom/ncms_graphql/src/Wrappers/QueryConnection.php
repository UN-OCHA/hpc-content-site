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
   * Return the article meta data for all article.
   *
   * @return array
   *   An array of article meta data, keyed by article id.
   */
  public function articleMetaData() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }
    $ids = array_values($result);

    // This is not ideal, because it makes heavy assumptions about the table
    // names, but it's the most performant we can do right now.
    $field_query = \Drupal::database()->select('node_field_data', 'n');
    $field_query->condition('nid', $ids, 'IN');
    $field_query->addJoin('LEFT', 'node__field_summary', 'summary', 'n.nid = summary.entity_id');
    $field_query->addJoin('LEFT', 'node__field_short_title', 'short_title', 'n.nid = short_title.entity_id');
    $field_query->addJoin('LEFT', 'node__field_automatically_visible', 'auto_visible', 'n.nid = auto_visible.entity_id');
    $field_query->addField('n', 'nid', 'id');
    $field_query->addField('n', 'status');
    $field_query->addField('n', 'created');
    $field_query->addField('n', 'changed', 'updated');
    $field_query->addField('n', 'title');
    $field_query->addField('n', 'force_update', 'forceUpdate');
    $field_query->addField('summary', 'field_summary_value', 'summary');
    $field_query->addField('short_title', 'field_short_title_value', 'title_short');
    $field_query->addField('auto_visible', 'field_automatically_visible_value', 'autoVisible');
    $result = $field_query->execute();
    return $result->fetchAllAssoc('id');
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