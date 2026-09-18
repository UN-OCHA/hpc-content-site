<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\ConnectionStatus;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GraphQL connection status data producer.
 */
#[Group('ncms_graphql')]
class ConnectionStatusTest extends UnitTestCase {

  /**
   * Tests that the producer returns the fixed health-check status.
   */
  public function testResolve(): void {
    $producer = new ConnectionStatus([], 'connection_status', []);

    $this->assertSame('connected', $producer->resolve());
  }

}
