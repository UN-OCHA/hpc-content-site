<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Implementation of the TermController class.
 */
class TermController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * Get the title of the preview.
   */
  public function addFormTitle(VocabularyInterface $taxonomy_vocabulary) {
    return $this->t('Add @title', [
      '@title' => strtolower($taxonomy_vocabulary->label()),
    ]);
  }

}
