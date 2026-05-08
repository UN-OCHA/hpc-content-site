<?php

namespace Drupal\Tests\ncms_publisher\Unit\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Log\NullLogger;

/**
 * Tests publisher refresh notification queue items.
 *
 * @group ncms_publisher
 */
class PublisherRefreshQueueTest extends UnitTestCase {

  /**
   * A valid delivery id.
   */
  const DELIVERY_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

  /**
   * Tests that queue items are sent as signed JSON requests.
   */
  public function testProcessItemSendsSignedRefreshNotification() {
    $secret = 'local-refresh-secret';
    $endpoint = 'http://example.com/webhooks/content/remote-refresh';
    $publisher = $this->createPublisher($endpoint, $secret);

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $this->callback(function (array $options) use ($secret) {
        $payload = Json::decode($options['body']);
        $this->assertSame([
          'source' => 'hpc_content_module',
          'type' => 'article',
          'id' => 123,
          'status' => 1,
          'changed' => 1710000000,
          'forceUpdate' => 1,
          'event' => 'saved',
          'deliveryId' => self::DELIVERY_ID,
        ], $payload);

        $this->assertSame('application/json', $options['headers']['Content-Type']);
        $this->assertSame(10, $options['timeout']);

        $timestamp = $options['headers']['X-NCMS-Timestamp'];
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $options['body'], $secret);
        $this->assertSame($expected, $options['headers']['X-NCMS-Signature']);
        return TRUE;
      }));

    $worker = $this->createWorker($publisher, $http_client);
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
      'status' => 1,
      'changed' => 1710000000,
      'force_update' => 1,
      'event' => 'saved',
      'delivery_id' => self::DELIVERY_ID,
    ]);
  }

  /**
   * Tests that disabled publishers are skipped.
   */
  public function testDisabledPublisherIsSkipped() {
    $publisher = $this->createPublisher('http://example.com/webhook', 'secret', FALSE);

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->never())->method('request');

    $worker = $this->createWorker($publisher, $http_client);
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
      'status' => 1,
      'changed' => 1710000000,
    ]);
  }

  /**
   * Tests that deleted queue items are sent with an unpublished status.
   */
  public function testProcessItemSendsDeletedRefreshNotification() {
    $endpoint = 'http://example.com/webhooks/content/remote-refresh';
    $publisher = $this->createPublisher($endpoint, 'local-refresh-secret');

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $this->callback(function (array $options) {
        $payload = Json::decode($options['body']);
        $this->assertSame(0, $payload['status']);
        $this->assertSame('deleted', $payload['event']);
        $this->assertSame(self::DELIVERY_ID, $payload['deliveryId']);
        return TRUE;
      }));

    $worker = $this->createWorker($publisher, $http_client);
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
      'status' => 0,
      'changed' => 1710000000,
      'event' => 'deleted',
      'delivery_id' => self::DELIVERY_ID,
    ]);
  }

  /**
   * Create a publisher mock.
   *
   * @param string $endpoint
   *   The refresh endpoint.
   * @param string $secret
   *   The refresh secret.
   * @param bool $enabled
   *   Whether refresh notifications are enabled.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface
   *   The publisher mock.
   */
  private function createPublisher(string $endpoint, string $secret, bool $enabled = TRUE): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('id')->willReturn('ghi');
    $publisher->method('refreshNotificationsEnabled')->willReturn($enabled);
    $publisher->method('getRefreshEndpoint')->willReturn($endpoint);
    $publisher->method('getRefreshSecret')->willReturn($secret);
    return $publisher;
  }

  /**
   * Create the queue worker under test.
   *
   * @param \Drupal\ncms_publisher\Entity\PublisherInterface $publisher
   *   The publisher returned from storage.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client mock.
   *
   * @return \Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue
   *   The queue worker.
   */
  private function createWorker(PublisherInterface $publisher, ClientInterface $http_client): PublisherRefreshQueue {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('ghi')->willReturn($publisher);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('publisher')->willReturn($storage);

    $worker = new PublisherRefreshQueue([], PublisherRefreshQueue::QUEUE_ID, []);
    $this->setProtectedProperty($worker, 'entityTypeManager', $entity_type_manager);
    $this->setProtectedProperty($worker, 'httpClient', $http_client);
    $this->setProtectedProperty($worker, 'logger', new NullLogger());
    return $worker;
  }

  /**
   * Set a protected property on an object.
   *
   * @param object $object
   *   The object.
   * @param string $property
   *   The property name.
   * @param mixed $value
   *   The property value.
   */
  private function setProtectedProperty(object $object, string $property, $value): void {
    $reflection = new \ReflectionProperty($object, $property);
    $reflection->setAccessible(TRUE);
    $reflection->setValue($object, $value);
  }

}
