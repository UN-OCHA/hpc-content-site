<?php

namespace Drupal\ncms_ui\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a taxonomy specific implementation for local action plugins.
 */
class LocalActionTaxonomy extends LocalActionDefault {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    // Subclasses may pull in the request or specific attributes as parameters.
    // The title from YAML file discovery may be a TranslatableMarkup object.
    $vocabulary = $request->attributes->get('taxonomy_vocabulary') ?? NULL;
    if (!$vocabulary) {
      return parent::getTitle($request);
    }
    return $this->t('Add @type', [
      '@type' => strtolower($vocabulary->label()),
    ]);
  }

}
