<?php

namespace Drupal\Tests\ncms_paragraphs\Unit;

use Drupal\ncms_paragraphs\Helpers\StringHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests the StringHelper service.
 */
class StringHelperTest extends UnitTestCase {

  /**
   * Data provider for testMakeCamelCase.
   */
  public static function makeCamelCaseDataProvider() {
    return [
      ['camel_case', FALSE, 'CamelCase'],
      ['Camel_Case', TRUE, 'camelCase'],
      ['Camel Case', TRUE, 'camel Case'],
      ['Camel-Case', TRUE, 'camel-Case'],
      ['Camel Case_Case', TRUE, 'camel CaseCase'],
      ['Camel-Case_Case', TRUE, 'camel-CaseCase'],
    ];
  }

  /**
   * Test making string camel case.
   */
  #[DataProvider('makeCamelCaseDataProvider')]
  public function testMakeCamelCase($string, $initial_lower_case, $result) {
    $this->assertEquals($result, StringHelper::makeCamelCase($string, $initial_lower_case));
  }

  /**
   * Data provider for testCamelCaseToUnderscoreCase.
   */
  public static function camelCaseToUnderscoreCaseDataProvider() {
    return [
      ['camelCase', 'camel_case'],
      ['camelCaseCase', 'camel_case_case'],
      ['CamelCaseCase', 'camel_case_case'],
      ['CCC', 'ccc'],
      ['CaCaCa', 'ca_ca_ca'],
    ];
  }

  /**
   * Test making string camel case.
   */
  #[DataProvider('camelCaseToUnderscoreCaseDataProvider')]
  public function testCamelCaseToUnderscoreCase($string, $result) {
    $this->assertEquals($result, StringHelper::camelCaseToUnderscoreCase($string));
  }

}
