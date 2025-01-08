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

    // Reassign components to regions when the layout has changed.
    $layout_parents = ['layout_paragraphs', 'layout'];
    $layout_id = NestedArray::getValue($input, $layout_parents);
    if ($layout_id && $plugin = self::getLayoutPluginManager()->createInstance($layout_id)) {
      /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $form_object */
      $form_object = $form_state->getFormObject();
      $layout = $form_object->getLayoutParagraphsLayout();
      $layout_section = $layout->getLayoutSection($form_object->getParagraph());
      if ($layout_section) {
        $components = $layout_section->getComponents() ?: [];
        $regions = $plugin->getPluginDefinition()->getRegions();
        if ($layout_section->getLayoutId() != $layout_id) {
          foreach (array_keys($regions) as $region_id) {
            if (empty($components)) {
              break;
            }
            $component = array_shift($components);
            $component->setSettings(['region' => $region_id]);
            $layout->setComponent($component->getEntity());
          }
          $tempstore = \Drupal::service('layout_paragraphs.tempstore_repository');
          $tempstore->set($layout);
        }
      }
    }
    return $form;
  }

  /**
   * Get the layout plugin manager.
   *
   * @return \Drupal\Core\Layout\LayoutPluginManagerInterface
   *   The layout plugin manager.
   */
  private static function getLayoutPluginManager() {
    return \Drupal::service('plugin.manager.core.layout');
  }

}
