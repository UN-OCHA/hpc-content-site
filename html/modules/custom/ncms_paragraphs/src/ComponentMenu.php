<?php

namespace Drupal\ncms_paragraphs;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Render\Element\VerticalTabs;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_paragraphs\Traits\VerticalTabsTrait;

/**
 * Service class for processing the component menu from layout paragraphs.
 *
 * @see ncms_paragraphs_preprocess_layout_paragraphs_builder_component_menu()
 */
class ComponentMenu {

  use VerticalTabsTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The paragraphs type settings service.
   *
   * @var \Drupal\ncms_paragraphs\ParagraphsTypeSettings
   */
  protected $paragraphsTypeSettings;

  /**
   * Public constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\ncms_paragraphs\ParagraphsTypeSettings $paragraph_type_settings
   *   The paragraphs type settings service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ParagraphsTypeSettings $paragraph_type_settings) {
    $this->entityTypeManager = $entityTypeManager;
    $this->paragraphsTypeSettings = $paragraph_type_settings;
  }

  /**
   * Preprocess the layout paragraphs component menu template.
   *
   * This turns the 2-category list into a custom list with multiple
   * categories using verticala tabs.
   *
   * @param array $variables
   *   The template variables to alter.
   *
   * @see ncms_paragraphs_theme_registry_alter()
   */
  public function preprocessTemplate(&$variables) {
    $items = array_merge($variables['types']['content'], $variables['types']['layout']);
    uasort($items, function ($a, $b) {
      return SortArray::sortByKeyString($a, $b, 'label');
    });

    // Prepare a pseudo form where we can attach vertical tabs.
    $form_state = new FormState();
    $complete_form = [];
    $form = [];
    $form['category_header'] = [
      '#type' => 'html_tag',
      '#tag' => 'h5',
      '#value' => $this->t('Choose a paragraph type from the following categories:'),
    ];
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#parents' => ['tabs'],
    ];

    $category_ids = array_keys($this->paragraphsTypeSettings->getCategoryOptions());
    $categories = [];
    foreach ($items as $item) {
      /** @var \Drupal\paragraphs\Entity\ParagraphsType $entity */
      $entity = $this->entityTypeManager->getStorage('paragraphs_type')->load($item['id']);
      $settings = $entity->getThirdPartySettings('ncms_paragraphs');
      if (!empty($settings['disabled'])) {
        continue;
      }

      $category = $settings['category'] ?? NULL;
      if (!$category) {
        continue;
      }
      $category_label = $this->paragraphsTypeSettings->getCategoryLabel($category) ?? $this->t('Unknown');

      $category_build = $categories[$category] ?? [
        '#title' => $category_label,
        'links' => [
          '#theme' => 'links',
          '#links' => [],
        ],
      ];
      $category_build['links']['#links'][] = [
        'title' => (string) $item['label'],
        'url' => $item['url_object'],
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
        ],
      ];
      $categories[$category] = $category_build;
    }

    $form['paragraph_categories'] = array_combine($category_ids, array_map(function ($category_id) use ($categories) {
      return $categories[$category_id];
    }, $category_ids));
    $form['paragraph_categories']['#tree'] = TRUE;

    // Let the tab element set itself up.
    VerticalTabs::processVerticalTabs($form['tabs'], $form_state, $complete_form);
    RenderElement::processGroup($form['tabs']['group'], $form_state, $complete_form);

    // Default tab is the first one. We have to set #value instead of the
    // #default_value, because this is not a real form and the normal form
    // processing doesn't work.
    $form['tabs']['tabs__active_tab']['#value'] = reset($categories);

    // Now go over the block categories, add some required properties and
    // run the process callback.
    $form['paragraph_categories']['#parents'] = ['paragraph_categories'];
    foreach (Element::children($form['paragraph_categories']) as $element_key) {
      $form['paragraph_categories'][$element_key]['#type'] = 'details';
      $form['paragraph_categories'][$element_key]['#group'] = 'tabs';
      $form['paragraph_categories'][$element_key]['#id'] = Html::getId($element_key);
      $form['paragraph_categories'][$element_key]['#parents'] = [
        'paragraph_categories',
        $element_key,
      ];
      $form['paragraph_categories'][$element_key]['#attributes'] = [
        'class' => [
          'paragraph-category-links',
        ],
      ];
      RenderElement::processGroup($form['paragraph_categories'][$element_key], $form_state, $complete_form);
    }
    $this->processVerticalTabs($form, $form_state);

    $variables['menu'] = $form;
    $variables['#attached']['library'][] = 'ncms_paragraphs/component_menu';
  }

}
