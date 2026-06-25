<?php

namespace Drupal\Tests\ncms_graphql\Unit\Wrappers;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\ncms_graphql\Wrappers\ContentSearchWrapper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the content search result wrapper.
 */
#[Group('ncms_graphql')]
class ContentSearchWrapperTest extends UnitTestCase {

  /**
   * Tests basic result accessors.
   */
  public function testAccessors(): void {
    $entities = [
      10 => $this->mockSearchEntity(10, 'First result'),
      20 => $this->mockSearchEntity(20, 'Second result'),
    ];

    $wrapper = new ContentSearchWrapper($entities);

    $this->assertSame(2, $wrapper->count());
    $this->assertSame([10, 20], $wrapper->ids());
    $this->assertSame($entities, $wrapper->items());
  }

  /**
   * Tests metadata extraction from wrapped entities.
   */
  public function testMetaData(): void {
    $wrapper = new ContentSearchWrapper([
      10 => $this->mockSearchEntity(10, 'First result'),
    ]);

    $metadata = $wrapper->metaData();

    $this->assertCount(1, $metadata);
    $this->assertSame(10, $metadata[10]->id);
    $this->assertSame('First result', $metadata[10]->title);
    $this->assertSame('Short first result', $metadata[10]->title_short);
    $this->assertSame('Summary text', $metadata[10]->summary);
    // The mock deliberately lacks the optional published/created/changed
    // interfaces, covering the wrapper's fallback metadata values.
    $this->assertSame(0, $metadata[10]->status);
    $this->assertNull($metadata[10]->created);
    $this->assertNull($metadata[10]->updated);
    $this->assertSame(1, $metadata[10]->autoVisible);
    $this->assertSame(1710001000, $metadata[10]->forceUpdate);
  }

  /**
   * Mocks a content entity carrying the fields read by the wrapper.
   */
  private function mockSearchEntity(int $id, string $label): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);
    $fields = [
      'field_short_title' => 'Short first result',
      'field_summary' => 'Summary text',
      'field_automatically_visible' => 1,
      'force_update' => 1710001000,
    ];

    $entity->method('id')->willReturn($id);
    $entity->method('label')->willReturn($label);
    $entity->method('hasField')->willReturnCallback(fn($field_name) => array_key_exists($field_name, $fields));
    $entity->method('get')->willReturnCallback(fn($field_name) => (object) [
      'value' => $fields[$field_name],
    ]);

    return $entity;
  }

}
