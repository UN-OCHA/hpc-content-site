<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\PublisherListBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the publisher list builder.
 */
#[Group('ncms_publisher')]
class PublisherListBuilderTest extends UnitTestCase {

  /**
   * Tests the list table header.
   */
  public function testBuildHeader(): void {
    $list_builder = $this->createListBuilder();

    $this->assertSame([
      'label' => 'Publisher',
      'id' => 'Machine name',
      'operations' => 'Operations',
    ], array_map('strval', $list_builder->buildHeader()));
  }

  /**
   * Tests the list table row.
   */
  public function testBuildRow(): void {
    $publisher = $this->mockPublisher('publisher_a', 'Publisher A');
    $list_builder = $this->createListBuilder();

    $row = $list_builder->buildRow($publisher);

    $this->assertSame('Publisher A', $row['label']);
    $this->assertSame('publisher_a', $row['id']);
    $this->assertSame(['#type' => 'operations'], $row['operations']['data']);
  }

  /**
   * Creates the list builder under test.
   */
  private function createListBuilder(): PublisherListBuilder {
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn($translated_string) => $translated_string->getUntranslatedString());
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    \Drupal::setContainer($container);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('id')->willReturn('publisher');

    // Generic entity operations are provided by core and covered elsewhere.
    // Mocking them keeps this test focused on the custom publisher columns.
    $list_builder = $this->getMockBuilder(PublisherListBuilder::class)
      ->setConstructorArgs([
        $entity_type,
        $this->createMock(EntityStorageInterface::class),
      ])
      ->onlyMethods(['buildOperations'])
      ->getMock();
    $list_builder->method('buildOperations')->willReturn(['#type' => 'operations']);
    return $list_builder;
  }

  /**
   * Mocks the publisher entity shown in the listing.
   */
  private function mockPublisher(string $id, string $label): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('id')->willReturn($id);
    $publisher->method('label')->willReturn($label);
    return $publisher;
  }

}
