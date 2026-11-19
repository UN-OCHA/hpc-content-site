<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\ncms_publisher\ReferencedContentResolver;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests resolving published content that references another entity.
 */
#[Group('ncms_publisher')]
class ReferencedContentResolverTest extends UnitTestCase {

  /**
   * Tests resolving a media reference through a paragraph.
   */
  public function testResolveMediaReferencedByParagraph(): void {
    $media = $this->mockEntity('media', 7);
    $paragraph = $this->mockEntity('paragraph', 88);
    $article = $this->createMock(ContentInterface::class);
    $article->method('getEntityTypeId')->willReturn('node');
    $article->method('id')->willReturn(123);
    $article->method('bundle')->willReturn('article');
    $article->method('isPublished')->willReturn(TRUE);

    $paragraph_storage = $this->createMock(EntityStorageInterface::class);
    $paragraph_storage->method('loadByProperties')->willReturnCallback(function (array $properties) use ($paragraph): array {
      return $properties === ['field_image' => 7] ? [$paragraph] : [];
    });

    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_storage->method('loadByProperties')->willReturnCallback(function (array $properties) use ($article): array {
      return $properties === ['field_paragraphs' => 88] ? [$article] : [];
    });

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnMap([
      ['node', $node_storage],
      ['paragraph', $paragraph_storage],
    ]);

    $entity_field_manager = $this->mockEntityFieldManager([
      'entity_reference' => [
        'paragraph' => ['field_image' => []],
      ],
      'entity_reference_revisions' => [
        'node' => ['field_paragraphs' => []],
      ],
    ], [
      'node' => [
        'field_paragraphs' => $this->mockReferenceField('paragraph'),
      ],
      'paragraph' => [
        'field_image' => $this->mockReferenceField('media'),
      ],
    ]);

    $resolver = new ReferencedContentResolver($entity_type_manager, $entity_field_manager);

    $this->assertSame([123 => $article], $resolver->resolve($media));
  }

  /**
   * Tests resolving media embedded in a paragraph's formatted text.
   */
  public function testResolveEmbeddedMedia(): void {
    $media = $this->mockEntity('media', 7, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa');
    $paragraph = $this->mockEntity('paragraph', 88);
    $article = $this->createMock(ContentInterface::class);
    $article->method('getEntityTypeId')->willReturn('node');
    $article->method('id')->willReturn(123);
    $article->method('bundle')->willReturn('article');
    $article->method('isPublished')->willReturn(TRUE);

    $paragraph_query = $this->createMock(QueryInterface::class);
    $paragraph_query->expects($this->once())->method('accessCheck')->with(FALSE)->willReturnSelf();
    $paragraph_query->expects($this->once())
      ->method('condition')
      ->with('field_text.value', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'CONTAINS')
      ->willReturnSelf();
    $paragraph_query->expects($this->once())->method('execute')->willReturn([88 => 88]);

    $paragraph_storage = $this->createMock(EntityStorageInterface::class);
    $paragraph_storage->method('getQuery')->willReturn($paragraph_query);
    $paragraph_storage->method('loadMultiple')->with([88 => 88])->willReturn([88 => $paragraph]);

    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_storage->method('loadByProperties')->willReturnCallback(function (array $properties) use ($article): array {
      return $properties === ['field_paragraphs' => 88] ? [$article] : [];
    });

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturnMap([
      ['node', $node_storage],
      ['paragraph', $paragraph_storage],
    ]);

    $entity_field_manager = $this->mockEntityFieldManager([
      'entity_reference_revisions' => [
        'node' => ['field_paragraphs' => []],
      ],
      'text_long' => [
        'paragraph' => ['field_text' => []],
      ],
    ], [
      'node' => [
        'field_paragraphs' => $this->mockReferenceField('paragraph'),
      ],
    ]);

    $resolver = new ReferencedContentResolver($entity_type_manager, $entity_field_manager);

    $this->assertSame([123 => $article], $resolver->resolve($media));
  }

  /**
   * Creates a content entity mock.
   */
  private function mockEntity(string $entity_type_id, int $id, ?string $uuid = NULL): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entity_type_id);
    $entity->method('id')->willReturn($id);
    $entity->method('uuid')->willReturn($uuid);
    return $entity;
  }

  /**
   * Creates an entity reference field storage definition mock.
   */
  private function mockReferenceField(string $target_type): FieldStorageDefinitionInterface {
    $field = $this->createMock(FieldStorageDefinitionInterface::class);
    $field->method('getSetting')->with('target_type')->willReturn($target_type);
    return $field;
  }

  /**
   * Creates an entity field manager mock with the supplied field maps.
   */
  private function mockEntityFieldManager(array $field_maps, array $storage_definitions): EntityFieldManagerInterface {
    $manager = $this->createMock(EntityFieldManagerInterface::class);
    $manager->method('getFieldMapByFieldType')->willReturnCallback(function (string $field_type) use ($field_maps): array {
      return $field_maps[$field_type] ?? [];
    });
    $manager->method('getFieldStorageDefinitions')->willReturnCallback(function (string $entity_type_id) use ($storage_definitions): array {
      return $storage_definitions[$entity_type_id] ?? [];
    });
    return $manager;
  }

}
