<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\media\MediaInterface;

/**
 * Defines an interface for entities with content spaces.
 */
interface MediaSpaceAwareInterface extends MediaInterface {

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
