<?php

namespace Drupal\ncms_paragraphs\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Base class for NCMS paragraphs.
 */
abstract class NcmsParagraphBase extends Paragraph implements NcmsParagraphInterface {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state) {}

}
