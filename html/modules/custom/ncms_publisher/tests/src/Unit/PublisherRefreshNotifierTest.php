<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue;
use Drupal\ncms_publisher\PublisherRefreshNotifier;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;

/**
 * Tests the publisher refresh notifier.
 */
#[Group('ncms_publisher')]
class PublisherRefreshNotifierTest extends UnitTestCase {

  /**
   * A valid delivery id.
   */
  const DELIVERY_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

  /**
   * Tests that only fully configured publishers receive queued items.
   */
  public function testEnqueueQueuesConfiguredPublishers(): void {
    $entity = $this->createContentEntity();
    $publisher = $this->createPublisher('ghi');
    $disabled = $this->createPublisher('disabled', FALSE);
    $missing_endpoint = $this->createPublisher('missing_endpoint', TRUE, NULL);
    $missing_secret = $this->createPublisher('missing_secret', TRUE, 'http://example.com/webhook', NULL);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('createItem')
      ->with($this->callback(function ($item) {
        $this->assertSame('ghi', $item->publisher);
        $this->assertSame('article', $item->type);
        $this->assertSame(123, $item->id);
        $this->assertSame(1710000000, $item->changed);
        $this->assertSame('saved', $item->event);
        $this->assertSame(self::DELIVERY_ID, $item->delivery_id);
        return TRUE;
      }));

    $queue_factory = $this->createMock(QueueFactory::class);
    $queue_factory->expects($this->once())
      ->method('get')
      ->with(PublisherRefreshQueue::QUEUE_ID)
      ->willReturn($queue);

    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('info')
      ->with('Queued @event refresh notification for @publisher: @type @id.');

    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn(self::DELIVERY_ID);

    $notifier = new PublisherRefreshNotifier(
      $this->createEntityTypeManager([$publisher, $disabled, $missing_endpoint, $missing_secret]),
      $queue_factory,
      $logger,
      $uuid
    );
    $notifier->enqueue($entity, 'saved');
  }

  /**
   * Creates a content entity mock.
   */
  private function createContentEntity(): ContentInterface {
    $entity = $this->createMock(ContentInterface::class);
    $entity->method('bundle')->willReturn('article');
    $entity->method('id')->willReturn(123);
    $entity->method('getChangedTime')->willReturn(1710000000);
    return $entity;
  }

  /**
   * Creates a publisher mock.
   */
  private function createPublisher(string $id, bool $enabled = TRUE, ?string $endpoint = 'http://example.com/webhook', ?string $secret = 'secret'): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('id')->willReturn($id);
    $publisher->method('refreshNotificationsEnabled')->willReturn($enabled);
    $publisher->method('getRefreshEndpoint')->willReturn($endpoint);
    $publisher->method('getRefreshSecret')->willReturn($secret);
    return $publisher;
  }

  /**
   * Creates an entity type manager returning the given publishers.
   *
   * @param \Drupal\ncms_publisher\Entity\PublisherInterface[] $publishers
   *   Publisher entities keyed by arbitrary ids.
   */
  private function createEntityTypeManager(array $publishers): EntityTypeManagerInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($publishers);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('publisher')->willReturn($storage);
    return $entity_type_manager;
  }

}
