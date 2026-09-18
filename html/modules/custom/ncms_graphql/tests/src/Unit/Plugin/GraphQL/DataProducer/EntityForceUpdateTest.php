<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\EntityForceUpdate;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the force-update timestamp data producer.
 */
#[Group('ncms_graphql')]
class EntityForceUpdateTest extends UnitTestCase {

  /**
   * Tests that the timestamp is returned when the entity exposes the field.
   */
  public function testResolveWithForceUpdateField(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->with('force_update')->willReturn(TRUE);
    $entity->method('get')->with('force_update')->willReturn((object) [
      'value' => 1710000000,
    ]);

    $producer = new EntityForceUpdate([], 'entity_force_update', []);

    $this->assertSame(1710000000, $producer->resolve($entity));
  }

  /**
   * Tests that entities without the field resolve to NULL.
   */
  public function testResolveWithoutForceUpdateField(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->with('force_update')->willReturn(FALSE);
    $entity->expects($this->never())->method('get');

    $producer = new EntityForceUpdate([], 'entity_force_update', []);

    $this->assertNull($producer->resolve($entity));
  }

}
