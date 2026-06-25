<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoDatasetLinkFormatter;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoFurtherReadingLinkFormatter;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests GHO link formatter URL helpers.
 */
#[Group('gho_fields')]
class GhoLinkFormattersTest extends UnitTestCase {

  /**
   * Tests that dataset link options are merged into the URL.
   */
  public function testDatasetBuildUrl(): void {
    $url = Url::fromUri('https://example.org/data');
    $item = $this->mockLinkItem($url, [
      'attributes' => ['class' => ['dataset-link']],
      'query' => ['download' => '1'],
    ]);

    $url = $this->createDatasetFormatter()->buildUrlForTest($item);

    $this->assertSame(['download' => '1'], $url->getOption('query'));
    $this->assertSame(['class' => ['dataset-link']], $url->getOption('attributes'));
  }

  /**
   * Tests that source metadata is not copied as a link attribute.
   */
  public function testFurtherReadingBuildUrlRemovesSourceAttribute(): void {
    $url = Url::fromUri('https://example.org/report');
    $item = $this->mockLinkItem($url, [
      'attributes' => [
        'source' => 'ReliefWeb',
        'class' => ['report-link'],
      ],
    ]);

    $url = $this->createFurtherReadingFormatter()->buildUrlForTest($item);

    $this->assertSame(['class' => ['report-link']], $url->getOption('attributes'));
  }

  /**
   * Creates a dataset formatter exposing the protected URL helper.
   */
  private function createDatasetFormatter(): GhoDatasetLinkFormatter {
    return new class('gho_dataset_link', [], $this->createMock(FieldDefinitionInterface::class), [], 'hidden', 'default', []) extends GhoDatasetLinkFormatter {

      /**
       * Public wrapper around the formatter's protected URL helper.
       */
      public function buildUrlForTest(LinkItemInterface $item): Url {
        return $this->buildUrl($item);
      }

    };
  }

  /**
   * Creates a further-reading formatter exposing the protected URL helper.
   */
  private function createFurtherReadingFormatter(): GhoFurtherReadingLinkFormatter {
    return new class('gho_further_reading_link', [], $this->createMock(FieldDefinitionInterface::class), [], 'hidden', 'default', []) extends GhoFurtherReadingLinkFormatter {

      /**
       * Public wrapper around the formatter's protected URL helper.
       */
      public function buildUrlForTest(LinkItemInterface $item): Url {
        return $this->buildUrl($item);
      }

    };
  }

  /**
   * Mocks a link field item with the magic options property used by formatters.
   */
  private function mockLinkItem(Url $url, array $options): LinkItemInterface {
    $item = $this->createMock(LinkItemInterface::class);
    $item->method('getUrl')->willReturn($url);
    $item->method('__get')->with('options')->willReturn($options);
    return $item;
  }

}
