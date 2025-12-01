<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\NodeInterface;

/**
 * Defines an interface for content entities.
 */
interface ContentInterface extends NodeInterface, BaseEntityInterface, IframeDisplayContentInterface {

  /**
   * Check if the content has any tags.
   *
   * @return bool
   *   TRUE if it can be published, FALSE otherwise.
   */
  public function hasTags();

  /**
   * Get the tags.
   *
   * @return string[]
   *   An array of tag names, keyed by term id.
   */
  public function getTags();

  /**
   * Get the tag entities.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of taxonomy term entities, keyed by term id.
   */
  public function getTagEntities();

  /**
   * Build the metadata used for diff displays.
   *
   * @return array
   *   A render array.
   */
  public function buildMetaDataForDiff();

}
