<?php

namespace Drupal\Tests\linked_responsive_image_media_formatter\Unit\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\image\ImageStyleInterface;
use Drupal\linked_responsive_image_media_formatter\Plugin\Field\FieldFormatter\LinkedResponsiveImageMediaFormatter;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the linked responsive image media formatter.
 */
#[Group('linked_responsive_image_media_formatter')]
class LinkedResponsiveImageMediaFormatterTest extends UnitTestCase {

  /**
   * Tests the formatter default settings.
   */
  public function testDefaultSettings(): void {
    $settings = LinkedResponsiveImageMediaFormatter::defaultSettings();

    $this->assertSame('', $settings['responsive_image_style']);
    $this->assertSame('', $settings['image_link']);
    $this->assertSame('', $settings['image_link_url']);
    $this->assertSame('image', $settings['image_alt']);
    $this->assertSame('', $settings['image_alt_value']);
    $this->assertFalse($settings['image_as_background']);
  }

  /**
   * Tests that only mapped responsive image styles are shown in settings.
   */
  public function testSettingsForm(): void {
    $hero = $this->mockResponsiveImageStyle('hero', 'Hero', TRUE);
    $empty = $this->mockResponsiveImageStyle('empty', 'Empty', FALSE);
    $style_storage = $this->mockStorage(['hero' => $hero, 'empty' => $empty]);
    $formatter = $this->createFormatter([
      'responsive_image_style' => 'hero',
      'image_link' => 'custom',
      'image_link_url' => 'https://example.org',
      'image_alt' => 'custom',
      'image_alt_value' => 'Custom alt',
      'image_as_background' => TRUE,
    ], $style_storage);

    $form = $formatter->settingsForm([], new FormState());

    $this->assertSame(['hero' => 'Hero'], $form['responsive_image_style']['#options']);
    $this->assertSame('hero', $form['responsive_image_style']['#default_value']);
    $this->assertTrue($form['responsive_image_style']['#description']['#access']);
    $this->assertSame('custom', $form['image_link']['#default_value']);
    $this->assertSame('https://example.org', $form['image_link_url']['#default_value']);
    $this->assertSame('Custom alt', $form['image_alt_value']['#default_value']);
    $this->assertTrue($form['image_as_background']['#default_value']);
  }

  /**
   * Tests the settings summary with and without a selected image style.
   */
  public function testSettingsSummary(): void {
    $style = $this->mockResponsiveImageStyle('hero', 'Hero', TRUE);
    $storage = $this->mockStorage([], ['hero' => $style]);
    $formatter = $this->createFormatter([
      'responsive_image_style' => 'hero',
      'image_link' => 'custom',
      'image_alt' => 'custom',
      'image_as_background' => TRUE,
    ], $storage);

    $this->assertSame([
      'Responsive image style: Hero',
      'Linking to custom URL',
      'Using custom alt text',
      'Image used as background',
    ], array_map('strval', $formatter->settingsSummary()));

    $formatter = $this->createFormatter(['responsive_image_style' => 'missing'], $this->mockStorage());

    $this->assertSame(['Select a responsive image style.'], array_map('strval', $formatter->settingsSummary()));
  }

  /**
   * Tests formatter applicability is limited to media reference fields.
   */
  public function testIsApplicable(): void {
    $this->assertTrue(LinkedResponsiveImageMediaFormatter::isApplicable($this->mockFieldDefinition('media')));
    $this->assertFalse(LinkedResponsiveImageMediaFormatter::isApplicable($this->mockFieldDefinition('node')));
  }

  /**
   * Tests selected responsive image style config is added as a dependency.
   */
  public function testCalculateDependencies(): void {
    $style = $this->mockResponsiveImageStyle('hero', 'Hero', TRUE);
    $storage = $this->mockStorage([], ['hero' => $style]);
    $formatter = $this->createFormatter(['responsive_image_style' => 'hero'], $storage);

    $this->assertSame([
      'config' => ['responsive_image.styles.hero'],
    ], $formatter->calculateDependencies());
  }

  /**
   * Tests cache metadata from the responsive image style and image styles.
   */
  public function testResponsiveImageStyleCacheMetadata(): void {
    $responsive_style = $this->mockResponsiveImageStyle('hero', 'Hero', TRUE, ['thumbnail', 'large']);
    $image_style_storage = $this->mockStorage([
      'thumbnail' => $this->mockImageStyle(['config:image.style.thumbnail']),
      'large' => $this->mockImageStyle(['config:image.style.large']),
    ]);
    $formatter = $this->createFormatter(image_style_storage: $image_style_storage);

    $metadata = $formatter->getResponsiveImageStyleCacheableMetadata($responsive_style);
    $build = [];
    $metadata->applyTo($build);

    $this->assertSame([], $build['#cache']['contexts']);
    $this->assertSame([
      'config:responsive_image.styles.hero',
      'config:image.style.thumbnail',
      'config:image.style.large',
    ], $build['#cache']['tags']);
    $this->assertSame(Cache::PERMANENT, $build['#cache']['max-age']);
  }

  /**
   * Tests custom URL extraction, validation, and custom alt cleanup.
   */
  public function testCustomUrlAndAltHelpers(): void {
    $this->setUnroutedUrlAssembler();

    $token = $this->createMock(Token::class);
    $token->method('replace')->willReturnMap([
      ['[media:url]', ['media' => 'm'], ['clear' => TRUE], '<a href="https://example.org/report">Report</a>'],
      ['[media:alt]', ['media' => 'm'], ['clear' => TRUE], 'A <strong>bold</strong> &amp; clear title'],
      ['[media:bad-url]', [], ['clear' => TRUE], 'not a valid URI'],
    ]);
    $formatter = $this->createFormatter([
      'image_link_url' => '[media:url]',
      'image_alt_value' => '[media:alt]',
    ], token: $token);

    $this->assertSame('https://example.org/report', $formatter->getCustomUrl(['media' => 'm']));
    $this->assertSame('A bold & clear title', $formatter->getCustomAlt(['media' => 'm']));

    $formatter->setSetting('image_link_url', '[media:bad-url]');
    $this->assertNull($formatter->getCustomUrl());
  }

  /**
   * Registers the URL assembler used when custom URLs are stringified.
   */
  private function setUnroutedUrlAssembler(): void {
    $assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $assembler->method('assemble')->willReturnCallback(static fn (string $uri) => $uri);

    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $assembler);
    \Drupal::setContainer($container);
  }

  /**
   * Creates a formatter through the production plugin factory.
   */
  private function createFormatter(array $settings = [], ?EntityStorageInterface $responsive_image_style_storage = NULL, ?EntityStorageInterface $image_style_storage = NULL, ?Token $token = NULL): LinkedResponsiveImageMediaFormatter {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnMap([
      ['responsive_image_style', $responsive_image_style_storage ?? $this->mockStorage()],
      ['image_style', $image_style_storage ?? $this->mockStorage()],
      ['media', $this->mockStorage()],
    ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('link_generator', $this->mockLinkGenerator());
    $container->set('current_user', $this->mockCurrentUser(TRUE));
    $container->set('renderer', $this->createMock(RendererInterface::class));
    $container->set('token', $token ?? $this->createMock(Token::class));

    $formatter = LinkedResponsiveImageMediaFormatter::create($container, [
      'field_definition' => $this->createMock(FieldDefinitionInterface::class),
      'settings' => $settings,
      'label' => 'hidden',
      'view_mode' => 'default',
      'third_party_settings' => [],
    ], 'linked_responsive_image_media_formatter', []);
    $formatter->setStringTranslation($this->getStringTranslationStub());
    return $formatter;
  }

  /**
   * Mocks entity storage for both loadMultiple() and load() callers.
   */
  private function mockStorage(array $multiple = [], array $single = []): EntityStorageInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($multiple);
    $storage->method('load')->willReturnCallback(static fn (string $id) => $single[$id] ?? NULL);
    return $storage;
  }

  /**
   * Mocks a responsive image style with cacheability and config metadata.
   */
  private function mockResponsiveImageStyle(string $id, string $label, bool $has_mappings, array $image_style_ids = []): ResponsiveImageStyleInterface {
    $style = $this->createMock(ResponsiveImageStyleInterface::class);
    $style->method('id')->willReturn($id);
    $style->method('label')->willReturn($label);
    $style->method('hasImageStyleMappings')->willReturn($has_mappings);
    $style->method('getImageStyleIds')->willReturn($image_style_ids);
    $style->method('getConfigDependencyKey')->willReturn('config');
    $style->method('getConfigDependencyName')->willReturn('responsive_image.styles.' . $id);
    $style->method('getCacheContexts')->willReturn([]);
    $style->method('getCacheTags')->willReturn(['config:responsive_image.styles.' . $id]);
    $style->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    return $style;
  }

  /**
   * Mocks image style cacheability used by responsive style metadata.
   */
  private function mockImageStyle(array $cache_tags): ImageStyleInterface {
    $style = $this->createMock(ImageStyleInterface::class);
    $style->method('getCacheContexts')->willReturn([]);
    $style->method('getCacheTags')->willReturn($cache_tags);
    $style->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    return $style;
  }

  /**
   * Mocks field storage target type for applicability checks.
   */
  private function mockFieldDefinition(string $target_type): FieldDefinitionInterface {
    $storage = $this->createMock(FieldStorageDefinitionInterface::class);
    $storage->method('getSetting')->with('target_type')->willReturn($target_type);

    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getFieldStorageDefinition')->willReturn($storage);
    return $definition;
  }

  /**
   * Mocks the link generator used for the settings form description.
   */
  private function mockLinkGenerator(): LinkGeneratorInterface {
    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->method('generate')->willReturn('Configure Responsive Image Styles');
    return $link_generator;
  }

  /**
   * Mocks the current user permission check for the settings form link.
   */
  private function mockCurrentUser(bool $can_administer): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->with('administer responsive image styles')->willReturn($can_administer);
    return $account;
  }

}
