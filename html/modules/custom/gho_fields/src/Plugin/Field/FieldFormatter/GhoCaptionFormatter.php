<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementations for 'gho_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_caption",
 *   label = @Translation("GHO caption formatter"),
 *   field_types = {
 *     "double_field"
 *   }
 * )
 */
class GhoCaptionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#location' => $item->first,
        '#caption' => $item->second,
        // This is needs to be set in a hook_preprocess_gho_caption_formatter().
        '#credits' => NULL,
        '#attributes' => new Attribute(),
        '#theme' => 'gho_caption_formatter',
      ];
    }

    return $element;
  }

}
