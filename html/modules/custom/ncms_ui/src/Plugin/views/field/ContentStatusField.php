<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Plugin\views\ContentBaseField;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Provides a field that shows the status of a node based on its versions.
 */
#[ViewsField("content_status_field")]
class ContentStatusField extends ContentBaseField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    if (!$row->_entity instanceof ContentInterface) {
      return NULL;
    }
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $row->_entity->getContentStatusLabel(),
      '#attributes' => [
        'class' => array_filter([
          'marker',
          $row->_entity->isPublished() ? 'marker--published' : NULL,
        ]),
      ],
    ];
    return $build;
  }

}
