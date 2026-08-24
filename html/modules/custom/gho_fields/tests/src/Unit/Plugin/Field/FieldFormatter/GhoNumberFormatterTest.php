<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoNumberFormatter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO number field formatter.
 */
#[Group('gho_fields')]
class GhoNumberFormatterTest extends UnitTestCase {

  /**
   * Tests default formatter settings.
   */
  public function testDefaultSettings(): void {
    $this->assertSame([
      'format' => 'decimal',
      'precision' => 1,
    ], GhoNumberFormatter::defaultSettings());
  }

  /**
   * Tests the settings form exposes format and compact precision controls.
   */
  public function testSettingsForm(): void {
    $formatter = $this->createFormatter([
      'format' => 'short',
      'precision' => 2,
    ]);

    $form = $formatter->settingsForm([], new FormState());

    $this->assertSame('select', $form['format']['#type']);
    $this->assertSame('short', $form['format']['#default_value']);
    $this->assertSame('select', $form['precision']['#type']);
    $this->assertSame(2, $form['precision']['#default_value']);
    $this->assertSame([
      ['value' => 'long'],
      ['value' => 'short'],
    ], $form['precision']['#states']['visible']['select[name$="[settings][format]"]']);
  }

  /**
   * Tests the settings summary includes the selected compact precision.
   */
  public function testSettingsSummary(): void {
    $formatter = $this->createFormatter([
      'format' => 'long',
      'precision' => 2,
    ]);

    $summary = array_map('strval', $formatter->settingsSummary());

    $this->assertSame('Format: long', $summary[0]);
    $this->assertSame('Precision: 2', $summary[1]);
    $this->assertStringStartsWith('Example:', $summary[2]);
  }

  /**
   * Tests number formatting dispatches according to formatter settings.
   */
  public function testFormatNumber(): void {
    $this->assertSame('NaN', (string) $this->createFormatter()->formatNumber('not-a-number', 'en'));
    $this->assertSame('1,200,000', $this->createFormatter()->formatNumber(1200000, 'en'));
    $this->assertSame('1.2 million', $this->createFormatter([
      'format' => 'long',
      'precision' => 1,
    ])->formatNumber(1200000, 'en'));
    $this->assertSame('1.2M', $this->createFormatter([
      'format' => 'short',
      'precision' => 1,
    ])->formatNumber(1200000, 'en'));
  }

  /**
   * Tests compact formatting boundaries and fallbacks.
   */
  public function testFormatNumberCompact(): void {
    $formatter = $this->createFormatter();

    $this->assertSame('-', $formatter->formatNumberCompact(0, 'en'));
    $this->assertSame(999, $formatter->formatNumberCompact(999, 'en'));
    $this->assertSame('1,200', $formatter->formatNumberCompact(1200, 'en'));
    $this->assertSame('12,000', $formatter->formatNumberCompact(12000, 'en', 'unknown'));
    $this->assertSame('1,000,000,000,000,000', $formatter->formatNumberCompact(1000000000000000, 'en'));
    $this->assertSame('0.01 million', $formatter->formatNumberCompact(12000, 'en', 'long', 1));
  }

  /**
   * Tests the supported plural rules used by compact formats.
   */
  public function testGetPluralFor(): void {
    $formatter = $this->createFormatter();

    $this->assertSame('zero', $formatter->getPluralFor(0, 'ar'));
    $this->assertSame('one', $formatter->getPluralFor(1, 'ar'));
    $this->assertSame('two', $formatter->getPluralFor(2, 'ar'));
    $this->assertSame('few', $formatter->getPluralFor(3, 'ar'));
    $this->assertSame('many', $formatter->getPluralFor(11, 'ar'));
    $this->assertSame('one', $formatter->getPluralFor(1, 'en'));
    $this->assertSame('other', $formatter->getPluralFor(1.2, 'en'));
    $this->assertSame('one', $formatter->getPluralFor(1, 'es'));
    $this->assertSame('one', $formatter->getPluralFor(1.5, 'fr'));
    $this->assertSame('other', $formatter->getPluralFor(2, 'fr'));
    $this->assertSame('other', $formatter->getPluralFor(2, 'unknown'));
  }

  /**
   * Creates a number formatter with optional settings.
   */
  private function createFormatter(array $settings = []): GhoNumberFormatter {
    $formatter = new GhoNumberFormatter(
      'gho_number',
      [],
      $this->createMock(FieldDefinitionInterface::class),
      $settings,
      'hidden',
      'default',
      []
    );
    $formatter->setStringTranslation($this->getStringTranslationStub());
    return $formatter;
  }

}
