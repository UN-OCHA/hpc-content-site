<?php

namespace Drupal\ncms_paragraphs\Helpers;

/**
 * Helper class for string handling.
 */
class StringHelper {

  /**
   * Make a string camel case.
   */
  public static function makeCamelCase($string, $initial_lower_case) {
    $string = str_replace('_', '', ucwords($string, '_'));
    if ($initial_lower_case) {
      $string = lcfirst($string);
    }
    return $string;
  }

  /**
   * Turn a camelcase string to an underscore separated string.
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   The output string.
   */
  public static function camelCaseToUnderscoreCase($string) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
  }

}
