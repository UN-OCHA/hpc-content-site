<?php

namespace Drupal\gho_general\StringTranslation;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\StringTranslation\TranslationManager;

/**
 * Defines a chained translation implementation combining multiple translators.
 *
 * This implementation enforce the use of the site's default language for the
 * the admin pages.
 */
class GhoTranslationManager extends TranslationManager {

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Constructs a TranslationManager object.
   *
   * @param \Drupal\Core\Language\LanguageDefault $default_language
   *   The default language.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   */
  public function __construct(LanguageDefault $default_language, AdminContext $admin_context) {
    parent::__construct($default_language);
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringTranslation($langcode, $string, $context) {
    // For admin pages, use the default language (English) for translations so
    // that the interface stays in English. Unfortunately there is no way to
    // differentiate between text from the interface and text from the content
    // so some strings in the content may appear untranslated.
    if ($this->adminContext->isAdminRoute()) {
      return $string;
    }
    return parent::getStringTranslation($langcode, $string, $context);
  }

}
