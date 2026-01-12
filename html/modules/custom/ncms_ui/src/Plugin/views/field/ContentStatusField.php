<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Core\Entity\EntityPublishedInterface;
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
    $entity = $row->_entity;
    if (!$entity instanceof EntityPublishedInterface) {
      return NULL;
    }
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $entity instanceof ContentInterface ? $entity->getContentStatusLabel() : ($entity->isPublished() ? $this->t('Published') : $this->t('Not published')),
      '#attributes' => [
        'class' => array_filter([
          'marker',
          $entity->isPublished() ? 'marker--published' : NULL,
        ]),
      ],
    ];
    return $build;
  }

}
