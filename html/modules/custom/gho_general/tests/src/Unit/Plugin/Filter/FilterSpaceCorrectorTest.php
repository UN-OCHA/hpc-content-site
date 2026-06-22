<?php

namespace Drupal\Tests\gho_general\Unit\Plugin\Filter;

use Drupal\gho_general\Plugin\Filter\FilterSpaceCorrector;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the space corrector text filter.
 */
#[Group('gho_general')]
class FilterSpaceCorrectorTest extends UnitTestCase {

  /**
   * Tests that non-breaking and repeated spaces are normalized.
   */
  public function testProcessNormalizesSpaces(): void {
    $filter = new FilterSpaceCorrector([], 'filter_space_corrector', [
      'provider' => 'gho_general',
    ]);
    $result = $filter->process("A&nbsp;B\xc2\xa0C  D", 'en');

    $this->assertSame('A B C D', $result->getProcessedText());
  }

}
