<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoInteractiveContentFormatter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO interactive content formatter helpers.
 */
#[Group('gho_fields')]
class GhoInteractiveContentFormatterTest extends UnitTestCase {

  /**
   * Tests extracting a valid Datawrapper iframe.
   */
  public function testExtractAttributesForDatawrapper(): void {
    $code = '<iframe id="datawrapper-chart-abc12" src="https://datawrapper.dwcdn.net/abc12/3/" width="600" height="400" title="Funding chart" aria-label="Chart"></iframe>';

    $this->assertSame([
      'id' => 'datawrapper-chart-abc12',
      'src' => 'https://datawrapper.dwcdn.net/abc12/3/',
      'width' => '600',
      'height' => '400',
      'title' => 'Funding chart',
      'aria-label' => 'Chart',
    ], GhoInteractiveContentFormatter::extractAttributes($code));
  }

  /**
   * Tests extracting a valid Power BI iframe.
   */
  public function testExtractAttributesForPowerBi(): void {
    $this->setUpStringTranslation();

    $code = '<iframe src="https://app.powerbi.com/reportEmbed?reportId=abc" height="500" title="Dashboard"></iframe>';
    $attributes = GhoInteractiveContentFormatter::extractAttributes($code);

    $this->assertSame('https://app.powerbi.com/reportEmbed?reportId=abc', $attributes['src']);
    $this->assertStringStartsWith('powerbi', $attributes['id']);
    $this->assertSame('500', $attributes['height']);
    $this->assertSame('Dashboard', $attributes['title']);
    $this->assertSame('Interactive content', (string) $attributes['aria-label']);
  }

  /**
   * Tests invalid iframe markup is rejected.
   */
  public function testExtractAttributesRejectsInvalidMarkup(): void {
    $this->assertNull(GhoInteractiveContentFormatter::extractAttributes(''));
    $this->assertNull(GhoInteractiveContentFormatter::extractAttributes('<p>No iframe</p>'));
    $this->assertNull(GhoInteractiveContentFormatter::extractAttributes('<iframe src="https://example.com" height="400" title="Unknown"></iframe>'));

    // Datawrapper embeds without a matching chart ID are parsed as partial
    // attributes and rejected by the later mandatory-attribute validation.
    $attributes = GhoInteractiveContentFormatter::extractAttributes('<iframe src="https://datawrapper.dwcdn.net/abc12/3/" height="400" title="Missing id"></iframe>');
    $this->assertSame(['id', 'src'], GhoInteractiveContentFormatter::validateMandatoryAttributes($attributes));
  }

  /**
   * Tests mandatory attribute validation.
   */
  public function testValidateMandatoryAttributes(): void {
    $this->assertSame([
      'id',
      'src',
      'height',
      'title',
    ], GhoInteractiveContentFormatter::validateMandatoryAttributes(NULL));

    $this->assertSame(['title'], GhoInteractiveContentFormatter::validateMandatoryAttributes([
      'id' => 'datawrapper-chart-abc12',
      'src' => 'https://datawrapper.dwcdn.net/abc12/3/',
      'height' => '400',
    ]));

    $this->assertSame([], GhoInteractiveContentFormatter::validateMandatoryAttributes([
      'id' => 'datawrapper-chart-abc12',
      'src' => 'https://datawrapper.dwcdn.net/abc12/3/',
      'height' => '400',
      'title' => 'Funding chart',
    ]));
  }

  /**
   * Tests positive numeric attribute validation.
   */
  public function testValidateNumber(): void {
    $this->assertTrue(GhoInteractiveContentFormatter::validateNumber('0'));
    $this->assertTrue(GhoInteractiveContentFormatter::validateNumber('400'));
    $this->assertFalse(GhoInteractiveContentFormatter::validateNumber('-1'));
    $this->assertFalse(GhoInteractiveContentFormatter::validateNumber('50%'));
    $this->assertFalse(GhoInteractiveContentFormatter::validateNumber('abc'));
  }

  /**
   * Sets up string translation for fallback iframe labels.
   */
  private function setUpStringTranslation(): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

}
