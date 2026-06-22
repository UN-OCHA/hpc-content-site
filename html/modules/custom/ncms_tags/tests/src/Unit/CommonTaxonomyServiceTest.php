<?php

namespace Drupal\Tests\ncms_tags\Unit;

use Drupal\ncms_tags\CommonTaxonomyService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the common taxonomy helper service.
 */
#[Group('ncms_tags')]
class CommonTaxonomyServiceTest extends UnitTestCase {

  /**
   * Tests taxonomy bundle to field name lookups.
   */
  public function testFieldNameLookups(): void {
    $service = new CommonTaxonomyService();

    $this->assertSame('field_theme', $service->getFieldNameForTaxonomyBundle('theme'));
    $this->assertNull($service->getFieldNameForTaxonomyBundle('unknown'));
    $this->assertSame([
      'document_type',
      'country',
      'year',
      'month',
      'theme',
    ], $service->getCommonTaxonomyBundles());
    $this->assertSame([
      'field_document_type',
      'field_country',
      'field_year',
      'field_month',
      'field_theme',
    ], $service->getCommonTaxonomyFieldNames());
  }

}
