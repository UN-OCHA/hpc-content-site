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
  const EMBED_ARCGIS = 'arcgis';
  const EMBED_PROVIDERS = [
    self::EMBED_POWERBI => 'https://app\.powerbi\.com/',
    self::EMBED_DATAWRAPPER => 'https://datawrapper\.dwcdn\.net/',
    self::EMBED_ARCGIS => 'https://experience\.arcgis\.com/',
  ];

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $attributes = static::extractAttributes($item->value);
      if (static::validateMandatoryAttributes($attributes) === []) {
        $provider = $this->getProviderForItem($item);
        if (!$provider) {
          continue;
        }
        $element[$delta] = [
          '#theme' => 'gho_interactive_content_formatter',
          '#attributes' => new Attribute($attributes),
          '#provider' => $provider,
        ];
      }
    }

    return $element;
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

    $provider = self::getProviderForSrc($src);
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
    else {
      $attributes['src'] = $src;
      $attributes['id'] = Html::getUniqueId($provider);
    }

    // Extract width.
    if ($iframe->hasAttribute('width')) {
      $width = trim($iframe->getAttribute('width'));
      if (static::validateNumber($width)) {
        $attributes['width'] = $width;
      }
    }

    // Extract height.
    $height = trim($iframe->getAttribute('height'));
    if (static::validateNumber($height)) {
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
   * Validate that a value is a positive number.
   *
   * @param int $value
   *   Value to check.
   *
   * @return bool
   *   TRUE if the value is a positive number.
   */
  public static function validateNumber($value) {
    if ((float) $value != $value) {
      return FALSE;
    }
    return filter_var((int) $value, FILTER_VALIDATE_INT, [
      'options' => ['min_range' => 0],
    ]) !== FALSE;
  }

  /**
   * Get the provider for the given field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return string
   *   The provider string if found.
   */
  private static function getProviderForItem(FieldItemInterface $item) {
    $attributes = static::extractAttributes($item->value);
    return self::getProviderForSrc($attributes['src']);
  }

  /**
   * Get the provider for the given source.
   *
   * @param string $src
   *   The src string.
   *
   * @return string
   *   The provider string if found.
   */
  private static function getProviderForSrc($src) {
    foreach (self::EMBED_PROVIDERS as $provider => $regex) {
      if (preg_match('~^' . $regex . '~', $src ?? '') === 1) {
        return $provider;
      }
    }
  }

}
