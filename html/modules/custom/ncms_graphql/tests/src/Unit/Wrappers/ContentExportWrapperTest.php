<?php

namespace Drupal\Tests\ncms_graphql\Unit\Wrappers;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\ncms_graphql\Wrappers\ContentExportWrapper;
use Drupal\Tests\UnitTestCase;
use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the content export result wrapper.
 */
#[Group('ncms_graphql')]
class ContentExportWrapperTest extends UnitTestCase {

  /**
   * Tests counting wrapped query results.
   */
  public function testCount(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())->method('range')->with(NULL, NULL)->willReturnSelf();
    $query->expects($this->once())->method('count')->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn(7);

    $wrapper = new ContentExportWrapper($query);

    $this->assertSame(7, $wrapper->count());
  }

  /**
   * Tests returning entity ids as a zero-based list.
   */
  public function testIds(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('execute')->willReturn([
      4 => 41,
      7 => 72,
    ]);

    $wrapper = new ContentExportWrapper($query);

    $this->assertSame([41, 72], $wrapper->ids());
  }

  /**
   * Tests that empty query results short-circuit wrapper methods.
   */
  public function testEmptyResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('execute')->willReturn([]);

    $wrapper = new ContentExportWrapper($query);

    $this->assertSame([], $wrapper->ids());
    $this->assertSame([], $wrapper->metaData());
    $this->assertSame([], $wrapper->items());
  }

  /**
   * Tests that item loading is deferred through the GraphQL entity buffer.
   */
  public function testItems(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('execute')->willReturn([
      4 => 41,
      7 => 72,
    ]);
    $query->method('getEntityTypeId')->willReturn('node');

    $buffer = $this->createMock(EntityBuffer::class);
    $buffer->expects($this->once())
      ->method('add')
      ->with('node', [41, 72])
      ->willReturn(fn() => ['loaded-node-41', 'loaded-node-72']);

    $container = new ContainerBuilder();
    $container->set('graphql.buffer.entity', $buffer);
    \Drupal::setContainer($container);

    $wrapper = new ContentExportWrapper($query);
    $deferred = $wrapper->items();
    SyncPromiseQueue::run();

    $this->assertSame(['loaded-node-41', 'loaded-node-72'], $deferred->result);
  }

}
