<?php

namespace Drupal\gho_layouts\Plugin\Layout;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformStateInterface;
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

    $element_parents = ['layout_paragraphs', 'config', 'column_widths'];
    $input = $form_state->getUserInput();
    if ($form_state instanceof SubformStateInterface) {
      $input = $form_state->getCompleteFormState()->getUserInput();
    }
    $columns_width = NestedArray::getValue($input, $element_parents);
    if (!array_key_exists($columns_width, $this->getWidthOptions())) {
      $columns_width = $this->getDefaultWidth();
      NestedArray::setValue($input, $element_parents, $columns_width);
      if ($form_state instanceof SubformStateInterface) {
        $form_state->getCompleteFormState()->setUserInput($input);
      }
      else {
        $form_state->setUserInput($input);
      }

    }
    return $form;
  }

}
