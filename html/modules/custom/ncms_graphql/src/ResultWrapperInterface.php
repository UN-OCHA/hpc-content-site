<?php

namespace Drupal\ncms_graphql;

/**
 * Interface for wrapping results.
 */
interface ResultWrapperInterface {

  /**
   * Return the number of results.
   *
   * @return int
   *   The number of results.
   */
  public function count();

  /**
   * Return the ids.
   *
   * @return int[]
   *   An array of ids.
   */
  public function ids();

  /**
   * Return the meta data for all content items.
   *
   * @return array
   *   An array of meta data, keyed by content id.
   */
  public function metaData();

  /**
   * Return all items.
   *
   * @return array|\GraphQL\Deferred
   *   The promise.
   */
  public function items();

}
