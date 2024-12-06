<?php

namespace Drupal\gho_layouts\Plugin\Layout;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Configurable three column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
abstract class MultiColumnLayoutBase extends MultiWidthLayoutBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $input = $form_state->getUserInput();
    $element_parents = ['layout_paragraphs', 'config', 'column_widths'];
    $columns_width = NestedArray::getValue($input, $element_parents);
    if (!array_key_exists($columns_width, $this->getWidthOptions())) {
      NestedArray::setValue($input, $element_parents, $this->getDefaultWidth());
      $form_state->setUserInput($input);
    }
    return $form;
  }

}
