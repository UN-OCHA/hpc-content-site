<?php

namespace Drupal\Tests\ncms_publisher\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\ncms_publisher\PublisherRefreshClient;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;

/**
 * Tests the publisher refresh client.
 */
#[Group('ncms_publisher')]
class PublisherRefreshClientTest extends UnitTestCase {

  /**
   * A valid delivery id.
   */
  const DELIVERY_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';

  /**
   * Tests that webhook requests are signed.
   */
  public function testPostSendsSignedJsonRequest(): void {
    $endpoint = 'http://example.com/webhooks/content/remote-refresh';
    $secret = 'local-refresh-secret';
    $payload = [
      'source' => PublisherRefreshClient::SOURCE,
      'type' => 'article',
      'id' => 123,
      'event' => 'saved',
      'deliveryId' => self::DELIVERY_ID,
    ];
    $response = $this->createMock(ResponseInterface::class);

    $http_client = $this->createMock(ClientInterface::class);
    $http_client->expects($this->once())
      ->method('request')
      ->with('POST', $endpoint, $this->callback(function (array $options) use ($payload, $secret) {
        $this->assertSame($payload, Json::decode($options['body']));
        $this->assertSame('application/json', $options['headers']['Content-Type']);
        $this->assertSame(10, $options['timeout']);

        $timestamp = $options['headers']['X-NCMS-Timestamp'];
        $expected = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $options['body'], $secret);
        $this->assertSame($expected, $options['headers']['X-NCMS-Signature']);
        return TRUE;
      }))
      ->willReturn($response);

    $client = new PublisherRefreshClient($http_client, $this->createMock(UuidInterface::class));

    $this->assertSame($response, $client->post($endpoint, $secret, $payload));
  }

  /**
   * Tests that ping payloads contain the fixed connection-check contract.
   */
  public function testBuildPingPayload(): void {
    $uuid = $this->createMock(UuidInterface::class);
    $uuid->method('generate')->willReturn(self::DELIVERY_ID);

    $client = new PublisherRefreshClient($this->createMock(ClientInterface::class), $uuid);

    $this->assertSame([
      'source' => PublisherRefreshClient::SOURCE,
      'type' => 'article',
      'id' => 1,
      'event' => 'ping',
      'deliveryId' => self::DELIVERY_ID,
    ], $client->buildPingPayload());
  }

  /**
   * Tests that basic auth settings are converted to request options.
   */
  public function testBuildRequestOptionsAddsBasicAuth(): void {
    $client = new PublisherRefreshClient($this->createMock(ClientInterface::class), $this->createMock(UuidInterface::class));

    $this->assertSame([
      RequestOptions::AUTH => ['viewer', 'viewer-pass'],
    ], $client->buildRequestOptions([
      'user' => 'viewer',
      'pass' => 'viewer-pass',
    ]));
  }

}
