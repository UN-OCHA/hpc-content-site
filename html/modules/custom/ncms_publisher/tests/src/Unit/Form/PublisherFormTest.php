<?php

namespace Drupal\Tests\ncms_publisher\Unit\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\Form\PublisherForm;
use Drupal\ncms_publisher\PublisherRefreshClient;
use Drupal\Tests\UnitTestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the publisher form.
 *
 * @group ncms_publisher
 */
class PublisherFormTest extends UnitTestCase {

  /**
   * Tests that the connection check sends a signed ping payload.
   */
  public function testCheckRefreshConnectionUsesRefreshClient(): void {
    $endpoint = 'http://example.com/webhooks/content/remote-refresh';
    $secret = 'stored-refresh-secret';
    $basic_auth = [
      'user' => 'viewer',
      'pass' => 'viewer-pass',
    ];
    $payload = [
      'source' => PublisherRefreshClient::SOURCE,
      'type' => 'article',
      'id' => 1,
      'event' => 'ping',
      'deliveryId' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
    ];

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')->willReturn(Response::HTTP_ACCEPTED);

    $refresh_client = $this->createMock(PublisherRefreshClient::class);
    $refresh_client->expects($this->once())
      ->method('buildPingPayload')
      ->willReturn($payload);
    $refresh_client->expects($this->once())
      ->method('buildRequestOptions')
      ->with($basic_auth)
      ->willReturn(['auth' => ['viewer', 'viewer-pass']]);
    $refresh_client->expects($this->once())
      ->method('post')
      ->with($endpoint, $secret, $payload, [
        'auth' => ['viewer', 'viewer-pass'],
        'http_errors' => FALSE,
      ])
      ->willReturn($response);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->once())->method('setRebuild');

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())->method('addStatus');
    $messenger->expects($this->never())->method('addError');

    $form = new PublisherForm();
    $form->setStringTranslation($this->createMock(TranslationInterface::class));
    $form->setMessenger($messenger);
    $this->setProtectedProperty($form, 'refreshClient', $refresh_client);
    $this->setProtectedProperty($form, 'entity', $this->createPublisher());
    $this->setProtectedProperty($form, 'configFactory', $this->createConfigFactory([
      'refresh_notifications.enabled' => TRUE,
      'refresh_notifications.endpoint' => $endpoint,
      'refresh_notifications.secret' => $secret,
      'refresh_notifications.basic_auth' => $basic_auth,
    ]));

    $build = [];
    $form->checkRefreshConnection($build, $form_state);
  }

  /**
   * Tests that an empty submitted secret keeps the existing refresh secret.
   */
  public function testSaveKeepsExistingRefreshSecretWhenSubmittedSecretIsEmpty(): void {
    $this->assertSubmittedRefreshSecretSavesAs('', 'existing-refresh-secret');
  }

  /**
   * Tests that a submitted secret replaces the existing refresh secret.
   */
  public function testSaveReplacesExistingRefreshSecretWhenSubmittedSecretIsSet(): void {
    $this->assertSubmittedRefreshSecretSavesAs('new-refresh-secret', 'new-refresh-secret');
  }

  /**
   * Tests that an empty submitted secret is valid when a secret already exists.
   */
  public function testValidateAllowsEmptyRefreshSecretWhenExistingSecretIsSet(): void {
    $submitted_values = [
      'refresh_notifications' => [
        'enabled' => TRUE,
        'endpoint' => 'http://example.com/webhooks/content/remote-refresh',
        'secret' => '',
      ],
    ];

    $publisher = $this->createPublisher();
    $publisher->method('getRefreshSecret')->willReturn('existing-refresh-secret');

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')
      ->willReturnCallback(fn(string $key) => $submitted_values[$key] ?? NULL);
    $form_state->expects($this->never())->method('setErrorByName');
    $form_state->expects($this->once())
      ->method('setValue')
      ->with('refresh_notifications', [
        'enabled' => TRUE,
        'endpoint' => 'http://example.com/webhooks/content/remote-refresh',
        'secret' => 'existing-refresh-secret',
      ]);

    $form = new PublisherForm();
    $form->setStringTranslation($this->createMock(TranslationInterface::class));
    $this->setProtectedProperty($form, 'entity', $publisher);

    $build = [];
    $form->validateForm($build, $form_state);
  }

  /**
   * Assert how a submitted refresh secret is saved.
   *
   * @param string $submitted_secret
   *   The submitted refresh secret.
   * @param string $expected_saved_secret
   *   The expected saved refresh secret.
   */
  private function assertSubmittedRefreshSecretSavesAs(string $submitted_secret, string $expected_saved_secret): void {
    if (!defined('SAVED_NEW')) {
      define('SAVED_NEW', 1);
    }

    $submitted_values = [
      'known_hosts' => 'example.com',
      'refresh_notifications' => [
        'enabled' => TRUE,
        'endpoint' => 'http://example.com/webhooks/content/remote-refresh',
        'secret' => $submitted_secret,
        'basic_auth' => [
          'user' => 'viewer',
          'pass' => 'viewer-pass',
        ],
      ],
    ];
    $saved_values = [];

    $publisher = $this->createPublisher();
    $publisher->method('getRefreshSecret')->willReturn('existing-refresh-secret');
    $publisher->method('label')->willReturn('GHI');
    $publisher->method('toUrl')->with('collection')->willReturn(Url::fromRoute('<front>'));
    $publisher->method('set')
      ->willReturnCallback(function (string $key, $value) use (&$saved_values, $publisher): PublisherInterface {
        $saved_values[$key] = $value;
        return $publisher;
      });
    $publisher->method('save')->willReturn(2);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')
      ->willReturnCallback(fn(string $key) => $submitted_values[$key] ?? NULL);
    $form_state->expects($this->once())
      ->method('setRedirectUrl')
      ->with($this->isInstanceOf(Url::class));

    $form = new PublisherForm();
    $form->setStringTranslation($this->createMock(TranslationInterface::class));
    $form->setMessenger($this->createMock(MessengerInterface::class));
    $this->setProtectedProperty($form, 'entity', $publisher);

    $form->save([], $form_state);

    $this->assertSame([
      'enabled' => TRUE,
      'endpoint' => 'http://example.com/webhooks/content/remote-refresh',
      'secret' => $expected_saved_secret,
      'basic_auth' => [
        'user' => 'viewer',
        'pass' => 'viewer-pass',
      ],
    ], $saved_values['refresh_notifications']);
  }

  /**
   * Create a publisher mock.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface
   *   The publisher mock.
   */
  private function createPublisher(): PublisherInterface {
    $publisher = $this->createMock(PublisherInterface::class);
    $publisher->method('getConfigDependencyName')->willReturn('ncms_publisher.publisher.ghi');
    $publisher->method('getRefreshBasicAuth')->willReturn(NULL);
    return $publisher;
  }

  /**
   * Create a config factory mock.
   *
   * @param array $values
   *   The runtime config values.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory mock.
   */
  private function createConfigFactory(array $values): ConfigFactoryInterface {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(fn(string $key) => $values[$key] ?? NULL);

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->with('ncms_publisher.publisher.ghi')
      ->willReturn($config);
    return $config_factory;
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
