<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer;
use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\EntityLoadMultipleByTitle;
use Drupal\ncms_graphql\Wrappers\ContentSearchWrapper;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the entity title search data producer.
 */
#[Group('ncms_graphql')]
class EntityLoadMultipleByTitleTest extends UnitTestCase {

  /**
   * Tests that empty search results add list cache tags.
   */
  public function testResolveEmptyResultsAddsListCacheTags(): void {
    $buffer = $this->createMock(EntityMatchingBuffer::class);
    $buffer->expects($this->once())
      ->method('addTitleString')
      ->with('node', 'Missing', ['article'])
      ->willReturn(fn() => []);

    $entity_type = $this->createMock(EntityTypeInterface::class);
    $entity_type->method('getListCacheTags')->willReturn(['node_list']);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getDefinition')->with('node')->willReturn($entity_type);

    $context = $this->createMock(FieldContext::class);
    $context->expects($this->once())->method('addCacheTags')->with(['node_list']);

    $producer = $this->createProducer($buffer, $entity_type_manager);
    $deferred = $producer->resolve('node', 'Missing', NULL, ['article'], TRUE, NULL, 'view', $context);
    SyncPromiseQueue::run();

    $this->assertInstanceOf(ContentSearchWrapper::class, $deferred->result);
    $this->assertSame(0, $deferred->result->count());
  }

  /**
   * Tests filtering unpublished and inaccessible entities from search results.
   */
  public function testResolveFiltersResults(): void {
    $allowed = $this->mockNode(1, TRUE, AccessResult::allowed(), 100);
    $denied = $this->mockNode(2, TRUE, AccessResult::forbidden(), 200);
    $unpublished = $this->mockNode(3, FALSE, AccessResult::allowed(), 300);

    $buffer = $this->createMock(EntityMatchingBuffer::class);
    $buffer->method('addTitleString')->willReturn(fn() => [
      1 => $allowed,
      2 => $denied,
      3 => $unpublished,
    ]);

    $context = $this->createMock(FieldContext::class);
    $context->expects($this->exactly(5))->method('addCacheableDependency');

    $producer = $this->createProducer($buffer);
    $deferred = $producer->resolve('node', 'Report', NULL, ['article'], TRUE, $this->createMock(AccountInterface::class), 'view', $context);
    // Entity filtering and wrapper creation happen in the deferred callback.
    SyncPromiseQueue::run();

    $this->assertInstanceOf(ContentSearchWrapper::class, $deferred->result);
    $this->assertSame([1], $deferred->result->ids());
    $this->assertSame([1 => $allowed], $deferred->result->items());
  }

  /**
   * Creates the data producer with the supplied entity matching buffer.
   */
  private function createProducer(EntityMatchingBuffer $buffer, ?EntityTypeManagerInterface $entity_type_manager = NULL): EntityLoadMultipleByTitle {
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager ?? $this->createMock(EntityTypeManagerInterface::class));
    $container->set('entity.repository', $this->createMock(EntityRepositoryInterface::class));
    $container->set('ncms_graphql.buffer.entity', $buffer);

    return EntityLoadMultipleByTitle::create($container, [], 'entity_search_by_title', []);
  }

  /**
   * Mocks a node result returned by the title matching buffer.
   */
  private function mockNode(int $id, bool $published, AccessResult $access_result, int $created): NodeInterface {
    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn($id);
    $node->method('isPublished')->willReturn($published);
    $node->method('access')->willReturn($access_result);
    $node->method('get')->with('created')->willReturn((object) [
      'value' => $created,
    ]);
    return $node;
  }

}
