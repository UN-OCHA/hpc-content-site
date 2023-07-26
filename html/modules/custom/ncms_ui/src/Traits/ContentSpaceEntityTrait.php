<?php

namespace Drupal\ncms_ui\Traits;

/**
 * Trait for entities supporting content spaces via ContentSpaceAwareInterface.
 *
 * This assumes that the implementing entity bundle has an entity reference
 * field named 'field_content_space'.
 */
trait ContentSpaceEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function getContentSpace() {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    return $this->get('field_content_space')->entity ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentSpace($content_space_id) {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    $this->get('field_content_space')->setValue(['target_id' => $content_space_id]);
  }

}
