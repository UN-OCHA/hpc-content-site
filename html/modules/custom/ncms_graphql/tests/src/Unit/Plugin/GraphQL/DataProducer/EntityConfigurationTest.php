<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\EntityConfiguration;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GraphQL entity configuration data producer.
 */
#[Group('ncms_graphql')]
class EntityConfigurationTest extends UnitTestCase {

  /**
   * Tests that nested objects are converted to strings before YAML encoding.
   */
  public function testMapObjectsToString(): void {
    $producer = new EntityConfiguration([], 'entity_configuration', []);
    $stringable = new class() {

      /**
       * Returns the object's scalar representation.
       */
      public function __toString(): string {
        return 'string value';
      }

    };

    $this->assertSame([
      'object' => 'string value',
      'nested' => [
        'object' => 'string value',
        'scalar' => 'kept',
      ],
    ], $producer->mapObjectsToString([
      'object' => $stringable,
      'nested' => [
        'object' => $stringable,
        'scalar' => 'kept',
      ],
    ]));
  }

}
