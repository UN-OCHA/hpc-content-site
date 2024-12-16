<?php

namespace Drupal\gho_layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\Form\EditComponentForm;

/**
 * Helper class for the layout select form element.
 */
class LayoutSelectHelper {

  /**
   * Process callback for layout select form elements.
   *
   * This assumes that there should only be a single component per layout
   * region. Based on that assumption, layouts with less regions than currently
   * configured components for a section will be disabled.
   */
  public static function processLayoutSelect(array &$element, FormStateInterface $form_state, array &$form) {
    $element['#attached']['library'][] = 'gho_layouts/layout_select';

    // Make the details wrapper a container, so it shows directly.
    $form['layout_paragraphs']['config']['#type'] = 'container';
    // Hide the "Administrative label input, we don't need that.
    $form['layout_paragraphs']['config']['label']['#access'] = FALSE;

    if (empty($form['#region_component_restrict'])) {
      // The rest here applies only to elements where some layout options
      // should be restricted.
      return $element;
    }

    /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $form_object */
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof EditComponentForm) {
      return $element;
    }
    $paragraph = $form_object->getParagraph();
    $layout_section = $form_object->getLayoutParagraphsLayout()->getLayoutSection($paragraph);
    $components = $layout_section->getComponents();
    $disabled = [];
    foreach (array_keys($element['#options']) as $layout) {
      $plugin = self::getLayoutPluginManager()->createInstance($layout);
      $regions = $plugin->getPluginDefinition()->getRegions();
      if (count($components) > count($regions)) {
        // If the number of currently existing components exceeds the number of
        // available regions in the layout options, disable the element.
        $disabled[] = $element['#options'][$layout];
        $element[$layout]['#disabled'] = TRUE;
      }
    }

    if (!empty($disabled)) {
      $form['layout_paragraphs']['disabled_message'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t('The following layout options have been disabled because the number of available regions for the layout is smaller than the currently configured components for this paragraph: @layouts', [
          '@layouts' => implode(', ', $disabled),
        ]),
        '#attributes' => ['class' => ['disabled-message']],
        '#weight' => 1,
      ];
    }
    return $element;
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
