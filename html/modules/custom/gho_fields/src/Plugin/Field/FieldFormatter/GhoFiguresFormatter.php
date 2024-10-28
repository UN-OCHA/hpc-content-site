<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\custom_field\Plugin\Field\FieldType\CustomItem;

/**
 * Plugin implementations for 'gho_figures' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_figures",
 *   label = @Translation("GHO figures formatter"),
 *   field_types = {
 *     "custom"
 *   }
 * )
 */
class GhoFiguresFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'large',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = [
      'large' => $this->t('Large figures'),
      'small' => $this->t('Small figures'),
    ];
    $elements['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $options,
      '#default_value' => $this->getSetting('format') ?? 'large',
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $format = $this->getSetting('format') ?? 'large';
    $summary[] = $this->t('Format: @format', ['@format' => $format]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    $figures = [];
    foreach ($items as $delta => $item) {
      if (!$item instanceof CustomItem) {
        continue;
      }
      $label = preg_replace("/^\s+|\s+$/u", "", $item->label ?? '');
      $value = preg_replace("/^\s+|\s+$/u", "", $item->value ?? '');
      if (!empty($label) && !empty($value)) {
        $figures[$delta] = [
          'label' => $label,
          'value' => $value,
          'footnote' => trim($item->footnote ?? ''),
        ];
      }
    }

    if (!empty($figures)) {
      $format = $this->getSetting('format') ?? 'large';
      $element['#theme'] = 'gho_figures_formatter__' . $format;
      $element['#figures'] = $figures;
      $element['#format'] = $format;
      $element['#attributes'] = new Attribute();
    }

    return $element;
  }

}
