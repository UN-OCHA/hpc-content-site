<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\ncms_ui\Entity\MediaInterface;
use Drupal\ncms_ui\Plugin\views\ContentBaseField;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Provides a field that shows the type of media usage (optional or mandatory).
 */
#[ViewsField("media_usage_type")]
class MediaUsageTypeField extends ContentBaseField {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $media_entity = $row->_entity;
    if (!$media_entity instanceof MediaInterface) {
      return;
    }

    $source_entity = $this->getSourceEntity($row);
    if (!$source_entity) {

    }
    $usage_type_required = $media_entity->isRequiredFor($source_entity);

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $usage_type_required ? $this->t('Required') : $this->t('Optional'),
    ];
    return $build;
  }

  /**
   * Get the source entity for this row.
   *
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The source entity or NULL.
   */
  private function getSourceEntity(ResultRow $row) {
    if (property_exists($row, 'paragraphs_item_field_data_entity_usage_id')) {
      $source_entity_id = $row->paragraphs_item_field_data_entity_usage_id;
      $source_entity_type_id = 'paragraph';
    }
    elseif (property_exists($row, 'entity_usage_media_source_id')) {
      $source_entity_id = $row->entity_usage_media_source_id;
      $source_entity_type_id = $row->entity_usage_media_source_type;
    }
    else {
      return NULL;
    }

    return $this->entityTypeManager->getStorage($source_entity_type_id)->load($source_entity_id);
  }

}
