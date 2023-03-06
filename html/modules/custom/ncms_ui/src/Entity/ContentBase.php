<?php

namespace Drupal\ncms_ui\Entity;

use Drupal\node\Entity\Node;

/**
 * Bundle class for organization nodes.
 */
class ContentBase extends Node implements ContentSpaceAwareInterface {

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
