<?php

namespace Drupal\Tests\ncms_publisher\Unit\Plugin\QueueWorker;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue;
use Drupal\ncms_publisher\PublisherRefreshClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;

/**
 * Tests publisher refresh notification queue items.
 */
#[Group('ncms_publisher')]
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
    $publisher = $this->createPublisher($endpoint, $secret, TRUE, [
      'user' => 'viewer',
      'pass' => 'viewer-pass',
    ]);

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $this->callback(function (array $options) use ($secret) {
        $payload = Json::decode($options['body']);
        $this->assertSame([
          'source' => 'hpc_content_module',
          'type' => 'article',
          'id' => 123,
          'changed' => 1710000000,
          'event' => 'saved',
          'deliveryId' => self::DELIVERY_ID,
        ], $payload);

        $this->assertSame('application/json', $options['headers']['Content-Type']);
        $this->assertSame(10, $options['timeout']);
        $this->assertSame(['viewer', 'viewer-pass'], $options[RequestOptions::AUTH]);

        $timestamp = $options['headers']['X-NCMS-Timestamp'];
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $options['body'], $secret);
        $this->assertSame($expected, $options['headers']['X-NCMS-Signature']);
        return TRUE;
      }));

    $worker = $this->createWorker($publisher, $this->createRefreshClient($http_client));
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
      'changed' => 1710000000,
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

    $worker = $this->createWorker($publisher, $this->createRefreshClient($http_client));
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
      'changed' => 1710000000,
    ]);
  }

  /**
   * Tests that deleted queue items are sent semantically.
   */
  public function testProcessItemSendsDeletedRefreshNotification() {
    $endpoint = 'http://example.com/webhooks/content/remote-refresh';
    $publisher = $this->createPublisher($endpoint, 'local-refresh-secret');

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $this->callback(function (array $options) {
        $payload = Json::decode($options['body']);
        $this->assertArrayNotHasKey('status', $payload);
        $this->assertSame('deleted', $payload['event']);
        $this->assertSame(self::DELIVERY_ID, $payload['deliveryId']);
        return TRUE;
      }));

    $worker = $this->createWorker($publisher, $this->createRefreshClient($http_client));
    $worker->processItem((object) [
      'publisher' => 'ghi',
      'type' => 'article',
      'id' => 123,
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
   * @param array|null $basic_auth
   *   The refresh basic auth settings.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface
   *   The publisher mock.
   */
  private function createPublisher(string $endpoint, string $secret, bool $enabled = TRUE, ?array $basic_auth = NULL): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('id')->willReturn('ghi');
    $publisher->method('refreshNotificationsEnabled')->willReturn($enabled);
    $publisher->method('getRefreshEndpoint')->willReturn($endpoint);
    $publisher->method('getRefreshSecret')->willReturn($secret);
    $publisher->method('getRefreshBasicAuth')->willReturn($basic_auth);
    return $publisher;
  }

  /**
   * Create the queue worker under test.
   *
   * @param \Drupal\ncms_publisher\Entity\PublisherInterface $publisher
   *   The publisher returned from storage.
   * @param \Drupal\ncms_publisher\PublisherRefreshClient $refresh_client
   *   The publisher refresh client.
   *
   * @return \Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue
   *   The queue worker.
   */
  private function createWorker(PublisherInterface $publisher, PublisherRefreshClient $refresh_client): PublisherRefreshQueue {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('ghi')->willReturn($publisher);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('publisher')->willReturn($storage);

    $worker = new PublisherRefreshQueue([], PublisherRefreshQueue::QUEUE_ID, []);
    $this->setProtectedProperty($worker, 'entityTypeManager', $entity_type_manager);
    $this->setProtectedProperty($worker, 'refreshClient', $refresh_client);
    $this->setProtectedProperty($worker, 'logger', new NullLogger());
    return $worker;
  }

  /**
   * Create a publisher refresh client.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client mock.
   *
   * @return \Drupal\ncms_publisher\PublisherRefreshClient
   *   The publisher refresh client.
   */
  private function createRefreshClient(ClientInterface $http_client): PublisherRefreshClient {
    return new PublisherRefreshClient($http_client, $this->createMock(UuidInterface::class));
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
