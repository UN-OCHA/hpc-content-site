<?php

namespace Drupal\ncms_ui\Entity;

/**
 * Defines an interface for entities with content spaces.
 */
interface ContentSpaceAwareInterface {

  /**
   * Get the content space that this content is assigned to.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity or NULL.
   */
  public function getContentSpace();

  /**
   * Set the content space that this content is assigned to.
   *
   * @param int $content_space_id
   *   An id of the content space.
   */
  public function setContentSpace($content_space_id);

}
