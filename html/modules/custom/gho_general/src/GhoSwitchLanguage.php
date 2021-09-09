<?php

namespace Drupal\gho_general;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provide trusted callbacks for rendering.
 */
class GhoSwitchLanguage implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'preRender',
      'postRender',
    ];
  }

  /**
   * Switch to the site default language before rendering the given element.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public static function preRender(array $element) {
    $element['#original_langcode'] = gho_general_switch_to_language();
    return $element;
  }

  /**
   * Switch back to the current language after rendering the given element.
   *
   * @param string $content
   *   Rendered element's content.
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   A renderable array.
   */
  public static function postRender($content, array $element) {
    if (isset($element['#original_langcode'])) {
      gho_general_switch_to_language($element['#original_langcode']);
      unset($element['#original_langcode']);
    }
    return $content;
  }

}
