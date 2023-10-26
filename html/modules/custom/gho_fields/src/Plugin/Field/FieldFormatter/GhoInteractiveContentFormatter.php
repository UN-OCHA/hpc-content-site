<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Template\Attribute;

/**
 * Plugin implementations for 'gho_interactive_content' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_interactive_content",
 *   label = @Translation("GHO interactive content formatter"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class GhoInteractiveContentFormatter extends FormatterBase {

  /**
   * Define the supported embed providers and their allowed embed base urls.
   */
  const EMBED_POWERBI = 'powerbi';
  const EMBED_DATAWRAPPER = 'datawrapper';
  const EMBED_PROVIDERS = [
    self::EMBED_POWERBI => 'https://app\.powerbi\.com/',
    self::EMBED_DATAWRAPPER => 'https://datawrapper\.dwcdn\.net/',
  ];

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $attributes = static::extractAttributes($item->value);
      if (static::validateMandatoryAttributes($attributes) === []) {
        $element[$delta] = [
          '#theme' => 'gho_interactive_content_formatter',
          '#attributes' => new Attribute($attributes),
          '#provider' => $this->isDatawrapper($item) ? self::EMBED_DATAWRAPPER : self::EMBED_POWERBI,
        ];
      }
    }

    return $element;
  }

  /**
   * Check if the given item represents a datawrapper embed.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return bool
   *   TRUE if datawrapper, FALSE otherwise.
   */
  private function isDatawrapper(FieldItemInterface $item) {
    $attributes = static::extractAttributes($item->value);
    return preg_match('~^' . self::EMBED_PROVIDERS['datawrapper'] . '~', $attributes['src'] ?? '') === 1;
  }

  /**
   * Parse the interactive content embed code for an interactive content.
   *
   * @param string $code
   *   Embed code that should be some HTML string with an iframe.
   *
   * @return array|null
   *   List of extracted attributes or NULL if the embed code doesn't contain
   *   an iframe.
   *
   * @see https://developer.datawrapper.de/docs/custom-embed-code
   */
  public static function extractAttributes($code) {
    if (empty($code)) {
      return NULL;
    }

    $attributes = [];
    $dom = Html::load($code);

    $iframe = $dom->getElementsByTagName('iframe')->item(0);
    if (!isset($iframe)) {
      return NULL;
    }

    if (!$iframe->hasAttribute('src') || !$iframe->hasAttribute('height')) {
      return NULL;
    }

    // First check if one of the supported embed providers is present.
    $src = $iframe->getAttribute('src');

    $pattern_datawrapper = '~^' . self::EMBED_PROVIDERS['datawrapper'] . '~';
    $pattern_powerbi = '~^' . self::EMBED_PROVIDERS['powerbi'] . '~';

    $provider = NULL;
    if (preg_match($pattern_datawrapper, $src) === 1) {
      $provider = self::EMBED_DATAWRAPPER;
    }
    elseif (preg_match($pattern_powerbi, $src) === 1) {
      $provider = self::EMBED_POWERBI;
    }
    if (!$provider) {
      return NULL;
    }

    if ($provider == self::EMBED_DATAWRAPPER) {
      // Extract id.
      $id = NULL;
      if ($iframe->hasAttribute('id')) {
        $id = preg_replace('/^datawrapper-chart-/', '', $iframe->getAttribute('id'));
        if (!empty($id)) {
          $attributes['id'] = 'datawrapper-chart-' . $id;
        }
      }

      // Extract url.
      if (!empty($id)) {
        $pattern = '~' . self::EMBED_PROVIDERS['datawrapper'] . preg_quote($id) . '/\d+/$~';
        if (preg_match($pattern, $src) === 1) {
          $attributes['src'] = $src;
        }
      }
    }
    elseif ($provider == self::EMBED_POWERBI) {
      $attributes['src'] = $src;
      $attributes['id'] = Html::getUniqueId('powerbi');
    }

    // Extract width.
    if ($iframe->hasAttribute('width')) {
      $width = trim($iframe->getAttribute('width'));
      if (static::validateInt($width)) {
        $attributes['width'] = $width;
      }
    }

    // Extract height.
    $height = trim($iframe->getAttribute('height'));
    if (static::validateInt($height)) {
      $attributes['height'] = $height;
    }

    // Extract title.
    if ($iframe->hasAttribute('title')) {
      $title = trim($iframe->getAttribute('title'));
      if (!empty($title)) {
        $attributes['title'] = $title;
      }
    }

    // Extract type.
    if ($iframe->hasAttribute('aria-label')) {
      $aria_label = trim($iframe->getAttribute('aria-label'));
    }
    $attributes['aria-label'] = $aria_label ?? t('Interactive content');

    return $attributes;
  }

  /**
   * Validate that all the mandatory attributes are present.
   *
   * @param array|null $attributes
   *   Attributes to validate.
   *
   * @return array
   *   List of missing mandatory attributes if any.
   */
  public static function validateMandatoryAttributes(?array $attributes) {
    $mandatory = ['id', 'src', 'height', 'title'];
    if (is_null($attributes)) {
      return $mandatory;
    }
    $missing = [];
    foreach ($mandatory as $attribute) {
      if (!isset($attributes[$attribute])) {
        $missing[] = $attribute;
      }
    }
    return $missing;
  }

  /**
   * Validate that a value is a positive integer.
   *
   * @param int $value
   *   Value to check.
   *
   * @return bool
   *   TRUE if the value is a positive integer.
   */
  public static function validateInt($value) {
    return filter_var($value, FILTER_VALIDATE_INT, [
      'options' => ['min_range' => 0],
    ]) !== FALSE;
  }

}
