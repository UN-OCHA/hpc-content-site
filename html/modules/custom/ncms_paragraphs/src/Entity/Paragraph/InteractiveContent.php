<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;

/**
 * Entity class for paragraphs of type interactive_content.
 */
class InteractiveContent extends NcmsParagraphBase {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state) {
    if (!empty($form['field_embed_code'])) {
      $form['field_embed_code']['#element_validate'][] = [
        $this,
        'validateEmbedCodeField',
      ];
    }
  }

  /**
   * Validate the embed code.
   */
  public function validateEmbedCodeField($element, FormStateInterface $form_state) {
    $element_parents = array_merge($element['#array_parents'], [0, 'value']);
    $embed_code = $form_state->getValue($element_parents);
    if (strpos($embed_code, '<script ')) {
      $form_state->setError($element, $this->t('The embed code must not contain any script tags.'));
    }
  }

}
