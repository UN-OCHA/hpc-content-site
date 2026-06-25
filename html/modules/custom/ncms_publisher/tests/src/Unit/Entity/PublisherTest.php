<?php

namespace Drupal\Tests\ncms_publisher\Unit\Entity;

use Drupal\ncms_publisher\Entity\Publisher;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the publisher config entity.
 */
#[Group('ncms_publisher')]
class PublisherTest extends UnitTestCase {

  /**
   * Tests known host helpers.
   */
  public function testKnownHosts(): void {
    $publisher = $this->createPublisher([
      'known_hosts' => " example.com \npreview.example.com",
    ]);

    $this->assertSame(['example.com', 'preview.example.com'], $publisher->getKnownHosts());
    $this->assertTrue($publisher->isKnownHost('example.com'));
    $this->assertFalse($publisher->isKnownHost('unknown.example.com'));
  }

  /**
   * Tests refresh notification accessors when fully configured.
   */
  public function testRefreshNotificationSettings(): void {
    $publisher = $this->createPublisher([
      'refresh_notifications' => [
        'enabled' => TRUE,
        'endpoint' => 'https://publisher.example.com/refresh',
        'secret' => 'shared-secret',
        'basic_auth' => [
          'user' => 'editor',
          'pass' => 'password',
        ],
      ],
    ]);

    $this->assertTrue($publisher->refreshNotificationsEnabled());
    $this->assertSame('https://publisher.example.com/refresh', $publisher->getRefreshEndpoint());
    $this->assertSame('shared-secret', $publisher->getRefreshSecret());
    $this->assertSame([
      'user' => 'editor',
      'pass' => 'password',
    ], $publisher->getRefreshBasicAuth());
  }

  /**
   * Tests refresh notification defaults.
   */
  public function testRefreshNotificationDefaults(): void {
    $publisher = $this->createPublisher();

    $this->assertFalse($publisher->refreshNotificationsEnabled());
    $this->assertNull($publisher->getRefreshEndpoint());
    $this->assertNull($publisher->getRefreshSecret());
    $this->assertNull($publisher->getRefreshBasicAuth());
  }

  /**
   * Tests basic auth defaults when only one side is configured.
   */
  public function testPartialRefreshBasicAuth(): void {
    $publisher = $this->createPublisher([
      'refresh_notifications' => [
        'basic_auth' => [
          'user' => 'editor',
        ],
      ],
    ]);

    $this->assertSame([
      'user' => 'editor',
      'pass' => '',
    ], $publisher->getRefreshBasicAuth());
  }

  /**
   * Creates a publisher config entity.
   */
  private function createPublisher(array $values = []): Publisher {
    return new Publisher($values + [
      'id' => 'test_publisher',
      'label' => 'Test publisher',
    ], 'publisher');
  }

}
