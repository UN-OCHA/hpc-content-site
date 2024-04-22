<?php

namespace Drupal\ncms_paragraphs\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Interface for NCMS paragraph entities.
 */
interface NcmsParagraphInterface extends ParagraphInterface {

  /**
   * Alter the entity form.
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state);

  /**
   * Preprocess the entity view.
   */
  public function preprocess(&$variables);

}
