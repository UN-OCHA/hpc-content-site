<?php

namespace Drupal\ncms_paragraphs;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Third party settings for paragraph types.
 */
class ParagraphsTypeSettings {

  use StringTranslationTrait;

  /**
   * Get the available category options.
   *
   * @return array
   *   An array of key-value pairs, suitable for use in select form elements.
   */
  public function getCategoryOptions() {
    return [
      'content' => $this->t('Content'),
      'media' => $this->t('Media'),
      'widget' => $this->t('Widgets'),
      'embed' => $this->t('Embedded'),
      'list' => $this->t('Lists'),
      'misc' => $this->t('Misc'),
    ];
  }

  /**
   * Get the label for a category.
   *
   * @param string $category
   *   The category machine name.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface|null
   *   The label for the category.
   */
  public function getCategoryLabel($category) {
    $categories = $this->getCategoryOptions();
    return array_key_exists($category, $categories) ? $categories[$category] : NULL;
  }

  /**
   * Form alter callback or paragraphs_type forms.
   */
  public function paragraphsTypeFormAlter(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\paragraphs\Entity\ParagraphsType $entity */
    $entity = $form_state->getFormObject()->getEntity();
    $category = $entity->getThirdPartySetting('ncms_paragraphs', 'category');
    $disabled = $entity->getThirdPartySetting('ncms_paragraphs', 'disabled');
    $options = $this->getCategoryOptions();

    $form['paragraph_settings'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Paragraph settings'),
    ];
    $form['paragraph_settings']['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#description' => $this->t('Choose the category under which this paragraph type will be shown in the paragraph type browser when adding new paragraphs to entities.'),
      '#options' => $options,
      '#default_value' => array_key_exists($category, $options) ? $category : NULL,
    ];
    $form['paragraph_settings']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable'),
      '#description' => $this->t('If checked, the paragraph type will be disabled. No new paragraphs of this type can be added. Existing ones will continue to work and can still be edited.'),
      '#default_value' => $disabled,
    ];

    $form['#entity_builders'][] = [$this, 'paragraphTypeFormBuilder'];
  }

  /**
   * Builder callback for paragraphs_type forms.
   */
  public function paragraphTypeFormBuilder($entity_type, $entity, &$form, FormStateInterface $form_state) {
    $paragraph_settings = $form_state->getValue(['paragraph_settings']);
    $entity->setThirdPartySetting('ncms_paragraphs', 'category', $paragraph_settings['category'] ?? NULL);
    $entity->setThirdPartySetting('ncms_paragraphs', 'disabled', $paragraph_settings['disabled'] ?? FALSE);
  }

}
