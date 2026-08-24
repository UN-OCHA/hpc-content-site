<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\PublisherManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the publisher manager service.
 */
#[Group('ncms_publisher')]
class PublisherManagerTest extends UnitTestCase {

  /**
   * Tests loading a publisher by id.
   */
  public function testGetPublisher(): void {
    $publisher = $this->mockPublisher('publisher_a');
    $manager = new PublisherManager(
      $this->mockEntityTypeManager([$publisher], ['publisher_a' => $publisher]),
      $this->createRequestStack()
    );

    $this->assertSame($publisher, $manager->getPublisher('publisher_a'));
  }

  /**
   * Tests known host aggregation across all publishers.
   */
  public function testGetKnownHosts(): void {
    $publisher_a = $this->mockPublisher('publisher_a', ['example.com', 'shared.example.com']);
    $publisher_b = $this->mockPublisher('publisher_b', ['shared.example.com', 'other.example.com']);
    $manager = new PublisherManager(
      $this->mockEntityTypeManager([$publisher_a, $publisher_b]),
      $this->createRequestStack()
    );

    $this->assertSame([
      'example.com',
      'shared.example.com',
      'other.example.com',
    ], array_values($manager->getKnownHosts()));
  }

  /**
   * Tests resolving the current publisher from request query arguments.
   */
  public function testGetCurrentPublisher(): void {
    $publisher = $this->mockPublisher('publisher_a');
    $manager = new PublisherManager(
      $this->mockEntityTypeManager([$publisher], ['publisher_a' => $publisher]),
      $this->createRequestStack(['publisher' => 'publisher_a'])
    );

    $this->assertSame($publisher, $manager->getCurrentPublisher());

    $manager = new PublisherManager(
      $this->mockEntityTypeManager([]),
      $this->createRequestStack()
    );

    $this->assertNull($manager->getCurrentPublisher());
  }

  /**
   * Tests that redirect URLs are limited to the current publisher hosts.
   */
  public function testGetCurrentRedirectUrl(): void {
    $publisher = $this->mockPublisher('publisher_a', ['allowed.example.com']);
    $entity_type_manager = $this->mockEntityTypeManager([$publisher], ['publisher_a' => $publisher]);

    $manager = new PublisherManager($entity_type_manager, $this->createRequestStack([
      'publisher' => 'publisher_a',
      'publisher_destination' => 'https://allowed.example.com/path',
    ]));
    $this->assertSame('https://allowed.example.com/path', $manager->getCurrentRedirectUrl());
    $this->assertInstanceOf(TrustedRedirectResponse::class, $manager->getCurrentRedirectResponse());

    $manager = new PublisherManager($entity_type_manager, $this->createRequestStack([
      'publisher' => 'publisher_a',
      'publisher_destination' => 'https://blocked.example.com/path',
    ]));
    $this->assertNull($manager->getCurrentRedirectUrl());

    $manager = new PublisherManager($entity_type_manager, $this->createRequestStack([
      'publisher' => 'publisher_a',
    ]));
    $this->assertNull($manager->getCurrentRedirectUrl());
    $this->assertNull($manager->getCurrentRedirectResponse());
  }

  /**
   * Mocks an entity type manager with publisher storage.
   *
   * @param \Drupal\ncms_publisher\Entity\PublisherInterface[] $publishers
   *   Publishers returned by loadMultiple().
   * @param \Drupal\ncms_publisher\Entity\PublisherInterface[] $publishers_by_id
   *   Publishers keyed by id for loadByProperties().
   */
  private function mockEntityTypeManager(array $publishers, array $publishers_by_id = []): EntityTypeManagerInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($publishers);
    $storage->method('loadByProperties')->willReturnCallback(function (array $properties) use ($publishers_by_id) {
      $id = $properties['id'] ?? NULL;
      return isset($publishers_by_id[$id]) ? [$id => $publishers_by_id[$id]] : [];
    });

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('publisher')->willReturn($storage);
    return $entity_type_manager;
  }

  /**
   * Mocks a publisher.
   */
  private function mockPublisher(string $id, array $known_hosts = []): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('id')->willReturn($id);
    $publisher->method('getKnownHosts')->willReturn($known_hosts);
    $publisher->method('isKnownHost')->willReturnCallback(fn($host) => in_array($host, $known_hosts, TRUE));
    return $publisher;
  }

  /**
   * Creates a request stack with optional query parameters.
   */
  private function createRequestStack(array $query = []): RequestStack {
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/', 'GET', $query));
    return $request_stack;
  }

}
