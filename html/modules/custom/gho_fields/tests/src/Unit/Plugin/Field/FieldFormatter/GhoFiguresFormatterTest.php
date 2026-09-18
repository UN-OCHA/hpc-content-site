<?php

namespace Drupal\Tests\gho_fields\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoFiguresFormatter;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO figures formatter settings.
 */
#[Group('gho_fields')]
class GhoFiguresFormatterTest extends UnitTestCase {

  /**
   * Tests default settings.
   */
  public function testDefaultSettings(): void {
    $this->assertSame(['format' => 'large'], GhoFiguresFormatter::defaultSettings());
  }

  /**
   * Tests settings form and summary use the selected format.
   */
  public function testSettingsFormAndSummary(): void {
    $formatter = $this->createFormatter(['format' => 'small']);

    $form = $formatter->settingsForm([], new FormState());
    $summary = array_map('strval', $formatter->settingsSummary());

    $this->assertSame('select', $form['format']['#type']);
    $this->assertSame('small', $form['format']['#default_value']);
    $this->assertSame([
      'large',
      'small',
    ], array_keys($form['format']['#options']));
    $this->assertSame(['Format: small'], $summary);
  }

  /**
   * Creates a figures formatter.
   */
  private function createFormatter(array $settings = []): GhoFiguresFormatter {
    $formatter = new GhoFiguresFormatter(
      'gho_figures',
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
