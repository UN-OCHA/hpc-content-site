<?php

namespace Drupal\Tests\gho_footnotes\Unit;

use Drupal\gho_footnotes\GhoFootnotes;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO footnotes render callback.
 */
#[Group('gho_footnotes')]
class GhoFootnotesTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once $this->root . '/modules/custom/gho_footnotes/gho_footnotes.module';
  }

  /**
   * Tests trusted callbacks.
   */
  public function testTrustedCallbacks(): void {
    $this->assertSame(['updateFootnotes'], GhoFootnotes::trustedCallbacks());
  }

  /**
   * Tests rendering leaves ordinary markup without footnote wrappers intact.
   */
  public function testUpdateFootnotesWithoutFootnoteMarkup(): void {
    $html = '<div><p>Plain text</p><gho-footnotes-placeholder id="gho-footnotes-placeholder-1"></gho-footnotes-placeholder></div>';

    $updated = GhoFootnotes::updateFootnotes($html, [
      '#view_mode' => 'preview',
      'footnotes' => ['#id' => 1],
    ]);

    $this->assertStringContainsString('<p>Plain text</p>', $updated);
    $this->assertStringNotContainsString('gho-footnotes-placeholder', $updated);
  }

}
