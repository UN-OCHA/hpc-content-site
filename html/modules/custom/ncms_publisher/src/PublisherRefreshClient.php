<?php

namespace Drupal\ncms_publisher;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Uuid\UuidInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Sends signed publisher refresh webhook requests.
 */
class PublisherRefreshClient {

  /**
   * The source id sent to HA.
   */
  const SOURCE = 'hpc_content_module';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Constructs a publisher refresh client.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generator.
   */
  public function __construct(ClientInterface $http_client, UuidInterface $uuid) {
    $this->httpClient = $http_client;
    $this->uuid = $uuid;
  }

  /**
   * Sends a signed refresh webhook request.
   *
   * @param string $endpoint
   *   The refresh endpoint.
   * @param string $secret
   *   The refresh secret.
   * @param array $payload
   *   The refresh payload.
   * @param array $options
   *   Additional request options.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  public function post(string $endpoint, string $secret, array $payload, array $options = []): ResponseInterface {
    $body = Json::encode($payload);
    $timestamp = (string) time();
    $signature = 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);

    $options += [
      'timeout' => 10,
      'headers' => [],
    ];
    $options['body'] = $body;
    $options['headers'] += [
      'Content-Type' => 'application/json',
      'X-NCMS-Timestamp' => $timestamp,
      'X-NCMS-Signature' => $signature,
    ];

    return $this->httpClient->request('POST', $endpoint, $options);
  }

  /**
   * Builds HTTP request options for publisher-specific connection settings.
   *
   * @param array|null $basic_auth
   *   The basic auth settings, if configured.
   *
   * @return array
   *   The request options.
   */
  public function buildRequestOptions(?array $basic_auth = NULL): array {
    $options = [];
    if (!empty($basic_auth['user']) || !empty($basic_auth['pass'])) {
      $options[RequestOptions::AUTH] = [
        $basic_auth['user'] ?? '',
        $basic_auth['pass'] ?? '',
      ];
    }
    return $options;
  }

  /**
   * Builds a ping payload for connection checks.
   *
   * @return array
   *   The ping payload.
   */
  public function buildPingPayload(): array {
    return [
      'source' => self::SOURCE,
      'type' => 'article',
      'id' => 1,
      'event' => 'ping',
      'deliveryId' => $this->uuid->generate(),
    ];
  }

}
