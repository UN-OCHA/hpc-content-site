<?php

namespace Drupal\Tests\ncms_graphql\Unit\GraphQL\Buffers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the entity title matching buffer.
 */
#[Group('ncms_graphql')]
class EntityMatchingBufferTest extends UnitTestCase {

  /**
   * Tests that searches for different bundles use separate buffers.
   */
  public function testSeparatesDifferentBundleSets(): void {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $buffer = $this->getMockBuilder(EntityMatchingBuffer::class)
      ->setConstructorArgs([$entity_type_manager])
      ->onlyMethods(['resolveBufferArray'])
      ->getMock();

    $buffer->expects($this->exactly(2))
      ->method('resolveBufferArray')
      ->willReturnCallback(function (array $items): array {
        $this->assertCount(1, $items);
        return [$items[0]['title']];
      });

    $document_resolver = $buffer->addTitleString('node', 'Afgha', ['document']);
    $article_resolver = $buffer->addTitleString('node', 'Global', ['article']);

    $this->assertSame('Afgha', $document_resolver());
    $this->assertSame('Global', $article_resolver());
  }

  /**
   * Tests that equivalent bundle sets share a buffer.
   */
  public function testGroupsEquivalentBundleSets(): void {
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $buffer = $this->getMockBuilder(EntityMatchingBuffer::class)
      ->setConstructorArgs([$entity_type_manager])
      ->onlyMethods(['resolveBufferArray'])
      ->getMock();

    $buffer->expects($this->once())
      ->method('resolveBufferArray')
      ->willReturnCallback(function (array $items): array {
        $this->assertCount(2, $items);
        return array_map(function (\ArrayObject $item): string {
          return $item['title'];
        }, $items);
      });

    $first_resolver = $buffer->addTitleString('node', 'First', ['article', 'document']);
    $second_resolver = $buffer->addTitleString('node', 'Second', ['document', 'article']);

    $this->assertSame('First', $first_resolver());
    $this->assertSame('Second', $second_resolver());
  }

  /**
   * Tests that buffered title searches are queried and matched per item.
   */
  public function testResolveBufferArray(): void {
    $query_conditions = [];
    $title_conditions = [];

    $title_group = $this->mockConditionGroup($title_conditions);
    $query = $this->createMock(QueryInterface::class);
    $query->method('orConditionGroup')->willReturn($title_group);
    $query->method('condition')
      ->willReturnCallback(function ($field, $value = NULL, $operator = NULL, $langcode = NULL) use (&$query_conditions, $query) {
        $query_conditions[] = [$field, $value, $operator, $langcode];
        return $query;
      });
    $query->expects($this->once())->method('accessCheck')->with(TRUE)->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn([1, 2, 3]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([1, 2, 3])
      ->willReturn([
        1 => $this->mockEntityWithLabel('Alpha project'),
        2 => $this->mockEntityWithLabel('Beta response'),
        3 => $this->mockEntityWithLabel('Unrelated'),
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $buffer = new EntityMatchingBuffer($entity_type_manager);
    $results = $buffer->resolveBufferArray([
      new \ArrayObject([
        'type' => 'node',
        'title' => 'Alpha',
        'bundles' => ['article'],
      ]),
      new \ArrayObject([
        'type' => 'node',
        'title' => 'Beta',
        'bundles' => ['article'],
      ]),
    ]);

    $this->assertSame([
      ['title', '%Alpha%', 'LIKE', NULL],
      ['title', '%Beta%', 'LIKE', NULL],
    ], $title_conditions);
    $this->assertSame([
      ['type', ['article'], 'IN', NULL],
      [$title_group, NULL, NULL, NULL],
    ], $query_conditions);
    $this->assertSame(['Alpha project'], array_values(array_map(fn(EntityInterface $entity) => $entity->label(), $results[0])));
    $this->assertSame(['Beta response'], array_values(array_map(fn(EntityInterface $entity) => $entity->label(), $results[1])));
  }

  /**
   * Tests that empty query results avoid entity loading.
   */
  public function testResolveBufferArrayWithNoMatches(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('orConditionGroup')->willReturn($this->mockConditionGroup());
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->expects($this->never())->method('loadMultiple');

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node')->willReturn($storage);

    $buffer = new EntityMatchingBuffer($entity_type_manager);
    $results = $buffer->resolveBufferArray([
      new \ArrayObject([
        'type' => 'node',
        'title' => 'Alpha',
        'bundles' => [],
      ]),
    ]);

    $this->assertSame([], $results[0]);
  }

  /**
   * Mocks an entity query condition group and optionally records conditions.
   */
  private function mockConditionGroup(array &$conditions = []): ConditionInterface {
    $group = $this->createMock(ConditionInterface::class);
    $group->method('condition')
      ->willReturnCallback(function ($field, $value = NULL, $operator = NULL, $langcode = NULL) use (&$conditions, $group) {
        $conditions[] = [$field, $value, $operator, $langcode];
        return $group;
      });
    return $group;
  }

  /**
   * Mocks an entity with the given label.
   */
  private function mockEntityWithLabel(string $label): EntityInterface {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('label')->willReturn($label);
    return $entity;
  }

}
