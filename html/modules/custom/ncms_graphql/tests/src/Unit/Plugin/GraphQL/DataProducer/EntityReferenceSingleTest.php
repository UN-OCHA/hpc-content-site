<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\EntityReferenceSingle;
use Drupal\Tests\UnitTestCase;
use GraphQL\Deferred;
use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the single entity reference data producer.
 */
#[Group('ncms_graphql')]
class EntityReferenceSingleTest extends UnitTestCase {

  /**
   * Tests that non-fieldable entities are ignored.
   */
  public function testResolveWithNonFieldableEntity(): void {
    $producer = $this->createProducer($this->createMock(EntityBuffer::class));

    $this->assertNull($producer->resolve(
      $this->createMock(EntityInterface::class),
      'field_related',
      NULL,
      NULL,
      NULL,
      TRUE,
      NULL,
      'view',
      $this->createMock(FieldContext::class)
    ));
  }

  /**
   * Tests that missing fields are ignored.
   */
  public function testResolveWithMissingField(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->with('field_related')->willReturn(FALSE);

    $producer = $this->createProducer($this->createMock(EntityBuffer::class));

    $this->assertNull($producer->resolve($entity, 'field_related', NULL, NULL, NULL, TRUE, NULL, 'view', $this->createMock(FieldContext::class)));
  }

  /**
   * Tests resolving one referenced entity by delta.
   */
  public function testResolveReturnsReferencedEntityByDelta(): void {
    $referenced_a = $this->mockReferencedEntity('article', AccessResult::allowed());
    $referenced_b = $this->mockReferencedEntity('article', AccessResult::allowed());
    $field_context = $this->createMock(FieldContext::class);
    $field_context->expects($this->exactly(2))->method('addCacheableDependency');

    $buffer = $this->createMock(EntityBuffer::class);
    $buffer->expects($this->once())
      ->method('add')
      ->with('node', [11, 12])
      ->willReturn(fn() => [$referenced_a, $referenced_b]);

    $producer = $this->createProducer($buffer);
    $deferred = $producer->resolve(
      $this->mockReferencingEntity('node', [11, 12]),
      'field_related',
      NULL,
      ['article'],
      1,
      TRUE,
      $this->createMock(AccountInterface::class),
      'view',
      $field_context
    );

    $this->assertInstanceOf(Deferred::class, $deferred);
    // The referenced entities are filtered inside the deferred callback.
    SyncPromiseQueue::run();

    $this->assertSame($referenced_b, $deferred->result);
  }

  /**
   * Tests that empty results add list cache tags.
   */
  public function testResolveEmptyReferencesAddsListCacheTags(): void {
    $buffer = $this->createMock(EntityBuffer::class);
    $buffer->method('add')->willReturn(fn() => []);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getListCacheTags')->willReturn(['node_list']);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->with('node')->willReturn($entity_type);

    $field_context = $this->createMock(FieldContext::class);
    $field_context->expects($this->once())->method('addCacheTags')->with(['node_list']);

    $producer = $this->createProducer($buffer, $entity_type_manager);
    $deferred = $producer->resolve($this->mockReferencingEntity('node', [11]), 'field_related', NULL, NULL, 0, TRUE, NULL, 'view', $field_context);
    SyncPromiseQueue::run();

    $this->assertNull($deferred->result);
  }

  /**
   * Creates the data producer with the supplied entity buffer.
   */
  private function createProducer(EntityBuffer $buffer, ?EntityTypeManagerInterface $entity_type_manager = NULL): EntityReferenceSingle {
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager ?? $this->createMock(EntityTypeManagerInterface::class));
    $container->set('entity.repository', $this->createMock(EntityRepositoryInterface::class));
    $container->set('graphql.buffer.entity', $buffer);

    return EntityReferenceSingle::create($container, [], 'entity_reference_single', []);
  }

  /**
   * Mocks the parent entity and its entity-reference field.
   */
  private function mockReferencingEntity(string $target_type, array $target_ids): FieldableEntityInterface {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getSetting')->with('target_type')->willReturn($target_type);

    $field = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $field->method('getValue')->willReturn(array_map(fn($target_id) => ['target_id' => $target_id], $target_ids));

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->with('field_related')->willReturn(TRUE);
    $entity->method('getFieldDefinition')->with('field_related')->willReturn($definition);
    $entity->method('get')->with('field_related')->willReturn($field);
    return $entity;
  }

  /**
   * Mocks a referenced entity with a bundle and access result.
   */
  private function mockReferencedEntity(string $bundle, AccessResult $access_result): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('bundle')->willReturn($bundle);
    $entity->method('access')->willReturn($access_result);
    return $entity;
  }

}
