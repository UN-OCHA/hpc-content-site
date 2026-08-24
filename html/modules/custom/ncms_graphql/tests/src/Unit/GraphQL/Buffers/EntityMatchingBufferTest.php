<?php

namespace Drupal\Tests\ncms_graphql\Unit\GraphQL\Buffers;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_graphql\GraphQL\Buffers\EntityMatchingBuffer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests buffering entity title searches.
 *
 * @group ncms_graphql
 */
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

}
