<?php

namespace Drupal\common_design_subtheme;

use Drupal\Component\Utility\Html;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provide trusted callbacks for rendering.
 */
class AddComponentClasses implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return [
      'add',
    ];
  }

  /**
   * Add component classes to HTML tags.
   *
   * @param string $html
   *   Html output.
   * @param array $element
   *   Render array.
   *
   * @return string
   *   Modified HTML.
   */
  public static function add($html, array $element) {
    $components = common_design_subtheme_get_components();
    if (empty($components)) {
      return $html;
    }

    $dom = Html::load($html);

    // Add the classes to the HTML tags for each component.
    foreach ($components as $tags) {
      foreach ($tags as $tag => $classes) {
        $nodes = $dom->getElementsByTagName($tag);
        foreach ($nodes as $node) {
          $existing = $node->getAttribute('class') ?? '';
          $classes = array_merge(preg_split("/\s+/", $existing), $classes);
          $node->setAttribute('class', trim(implode(' ', array_unique($classes))));
        }
      }
    }

    $html = Html::serialize($dom);
    return trim($html);
  }

}
