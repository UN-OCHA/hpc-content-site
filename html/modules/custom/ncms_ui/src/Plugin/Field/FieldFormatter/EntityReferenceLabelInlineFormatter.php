<?php

namespace Drupal\ncms_ui\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Render\Element;

/**
 * Plugin implementation of the 'entity reference label inline' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_label_inline",
 *   label = @Translation("Label (inline)"),
 *   description = @Translation("Display the label of the referenced entities inline."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceLabelInlineFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    $items = [];
    foreach (Element::children($elements) as $delta) {
      $items[] = $elements[$delta];
      unset($elements[$delta]);
    }
    $elements[0] = [
      '#type' => 'inline_template',
      '#template' => '{{ items | safe_join(separator) }}',
      '#context' => ['separator' => ', ', 'items' => $items],
    ];
    return $elements;
  }

}
