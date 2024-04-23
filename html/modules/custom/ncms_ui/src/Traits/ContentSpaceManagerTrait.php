<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\ncms_ui\ContentSpaceManager;

/**
 * Trait for entities supporting content spaces via ContentSpaceAwareInterface.
 *
 * This assumes that the implementing entity bundle has an entity reference
 * field named 'field_content_space'.
 */
trait ContentSpaceManagerTrait {

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

  /**
   * Get the content space manager service.
   *
   * @return \Drupal\ncms_ui\ContentSpaceManager
   *   The content space manager.
   */
  public function getContentSpaceManager() {
    if (!$this->contentSpaceManager) {
      $this->contentSpaceManager = \Drupal::service('ncms_ui.content_space.manager');
    }
    return $this->contentSpaceManager;
  }

  /**
   * Set the content space manager service.
   *
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_space_manager
   *   The content space manager.
   */
  public function setContentSpaceManager(ContentSpaceManager $content_space_manager) {
    $this->contentSpaceManager = $content_space_manager;
  }

}
