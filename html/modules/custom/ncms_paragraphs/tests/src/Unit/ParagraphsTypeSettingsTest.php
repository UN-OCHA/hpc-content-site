<?php

namespace Drupal\Tests\ncms_paragraphs\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_paragraphs\ParagraphsTypeSettings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests paragraph type third-party settings helpers.
 */
#[Group('ncms_paragraphs')]
class ParagraphsTypeSettingsTest extends UnitTestCase {

  /**
   * Tests category option and label helpers.
   */
  public function testCategoryOptionsAndLabels(): void {
    $settings = $this->createSettings();
    $options = array_map('strval', $settings->getCategoryOptions());

    $this->assertSame([
      'content' => 'Content',
      'media' => 'Media',
      'widget' => 'Widgets',
      'embed' => 'Embedded',
      'list' => 'Lists',
      'misc' => 'Misc',
    ], $options);
    $this->assertSame('Content', (string) $settings->getCategoryLabel('content'));
    $this->assertNull($settings->getCategoryLabel('unknown'));
  }

  /**
   * Tests adding paragraph settings fields to the paragraph type form.
   */
  public function testParagraphsTypeFormAlter(): void {
    $entity = new class() {

      /**
       * Returns stored third-party settings for the form alter callback.
       */
      public function getThirdPartySetting(string $provider, string $key) {
        $settings = [
          'category' => 'content',
          'disabled' => TRUE,
        ];
        return $provider === 'ncms_paragraphs' ? $settings[$key] : NULL;
      }

    };

    $form_object = new class($entity) {

      /**
       * Constructs the form object.
       */
      public function __construct(private readonly object $entity) {
      }

      /**
       * Returns the paragraph type entity being edited.
       */
      public function getEntity(): object {
        return $this->entity;
      }

    };

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getFormObject')->willReturn($form_object);

    $form = [];
    $settings = $this->createSettings();
    $settings->paragraphsTypeFormAlter($form, $form_state);

    $this->assertSame('fieldset', $form['paragraph_settings']['#type']);
    $this->assertSame('select', $form['paragraph_settings']['category']['#type']);
    $this->assertSame('content', $form['paragraph_settings']['category']['#default_value']);
    $this->assertTrue($form['paragraph_settings']['disabled']['#default_value']);
    $this->assertContains([$settings, 'paragraphTypeFormBuilder'], $form['#entity_builders']);
  }

  /**
   * Tests writing submitted paragraph settings back to the entity.
   */
  public function testParagraphTypeFormBuilder(): void {
    $entity = new class() {

      /**
       * The third-party settings written by the form builder.
       *
       * @var array
       */
      public array $settings = [];

      /**
       * Records written third-party settings.
       */
      public function setThirdPartySetting(string $provider, string $key, mixed $value): void {
        $this->settings[$provider][$key] = $value;
      }

    };

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->with(['paragraph_settings'])->willReturn([
      'category' => 'media',
      'disabled' => TRUE,
    ]);

    $form = [];
    $this->createSettings()->paragraphTypeFormBuilder('paragraphs_type', $entity, $form, $form_state);

    $this->assertSame([
      'ncms_paragraphs' => [
        'category' => 'media',
        'disabled' => TRUE,
      ],
    ], $entity->settings);
  }

  /**
   * Creates the settings service with passthrough string translation.
   */
  private function createSettings(): ParagraphsTypeSettings {
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn($translated_string) => $translated_string->getUntranslatedString());

    $settings = new ParagraphsTypeSettings();
    $settings->setStringTranslation($translation);
    return $settings;
  }

}
