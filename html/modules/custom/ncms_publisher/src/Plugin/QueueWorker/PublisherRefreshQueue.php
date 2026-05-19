<?php

namespace Drupal\ncms_publisher\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_publisher\Entity\PublisherInterface;
use Drupal\ncms_publisher\PublisherRefreshClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends remote refresh notifications to publishers.
 *
 * The contrib Webhooks module is a possible generic alternative:
 * https://www.drupal.org/project/webhooks. This sender stays custom while that
 * module is alpha and does not provide the replay protection, secret handling,
 * and narrow CM-to-HA contract we need here.
 */
#[QueueWorker(
  id: 'ncms_publisher_refresh_notification',
  title: new TranslatableMarkup('Publisher refresh notification'),
  cron: ['time' => 60]
)]
final class PublisherRefreshQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Queue id for publisher refresh notifications.
   */
  const QUEUE_ID = 'ncms_publisher_refresh_notification';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The publisher refresh client.
   *
   * @var \Drupal\ncms_publisher\PublisherRefreshClient
   */
  protected $refreshClient;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->refreshClient = $container->get('ncms_publisher.refresh_client');
    $instance->logger = $container->get('logger.channel.ncms_publisher');
    $instance->uuid = $container->get('uuid');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $publisher = $this->loadPublisher($data->publisher ?? NULL);
    if (!$publisher || !$publisher->refreshNotificationsEnabled()) {
      return;
    }

    $payload = [
      'source' => PublisherRefreshClient::SOURCE,
      'type' => $data->type,
      'id' => (int) $data->id,
      'changed' => (int) $data->changed,
      'event' => $data->event ?? 'saved',
      'deliveryId' => $data->delivery_id ?? $this->uuid->generate(),
    ];

    $this->refreshClient->post(
      $publisher->getRefreshEndpoint(),
      $publisher->getRefreshSecret(),
      $payload,
      $this->refreshClient->buildRequestOptions($publisher->getRefreshBasicAuth())
    );

    $this->logger->info('Sent @event refresh notification to @publisher for @type @id.', [
      '@event' => $payload['event'],
      '@publisher' => $publisher->id(),
      '@type' => $data->type,
      '@id' => $data->id,
    ]);
  }

  /**
   * Load a publisher config entity.
   *
   * @param string|null $publisher_id
   *   The publisher id.
   *
   * @return \Drupal\ncms_publisher\Entity\PublisherInterface|null
   *   The publisher if found.
   */
  private function loadPublisher(?string $publisher_id): ?PublisherInterface {
    if (!$publisher_id) {
      return NULL;
    }
    $publisher = $this->entityTypeManager->getStorage('publisher')->load($publisher_id);
    return $publisher instanceof PublisherInterface ? $publisher : NULL;
  }

}
