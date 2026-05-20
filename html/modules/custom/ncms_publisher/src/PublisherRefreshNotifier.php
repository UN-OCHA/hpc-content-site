<?php

namespace Drupal\ncms_publisher;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\ncms_publisher\Plugin\QueueWorker\PublisherRefreshQueue;
use Drupal\ncms_ui\Entity\ContentInterface;
use Psr\Log\LoggerInterface;

/**
 * Queues refresh notifications for configured publishers.
 */
class PublisherRefreshNotifier {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

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
   * Constructs a publisher refresh notifier.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueueFactory $queue_factory, LoggerInterface $logger, UuidInterface $uuid) {
    $this->entityTypeManager = $entity_type_manager;
    $this->queueFactory = $queue_factory;
    $this->logger = $logger;
    $this->uuid = $uuid;
  }

  /**
   * Queue notifications for all configured publishers.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $entity
   *   The content entity.
   * @param string $event
   *   The event name.
   */
  public function enqueue(ContentInterface $entity, string $event): void {
    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface[] $publishers */
    $publishers = $this->entityTypeManager->getStorage('publisher')->loadMultiple();
    foreach ($publishers as $publisher) {
      if (!$publisher->refreshNotificationsEnabled() || !$publisher->getRefreshEndpoint() || !$publisher->getRefreshSecret()) {
        continue;
      }

      $this->queueFactory->get(PublisherRefreshQueue::QUEUE_ID)->createItem((object) [
        'publisher' => $publisher->id(),
        'type' => $entity->bundle(),
        'id' => (int) $entity->id(),
        'changed' => (int) $entity->getChangedTime(),
        'event' => $event,
        'delivery_id' => $this->uuid->generate(),
      ]);

      $this->logger->info('Queued @event refresh notification for @publisher: @type @id.', [
        '@event' => $event,
        '@publisher' => $publisher->id(),
        '@type' => $entity->bundle(),
        '@id' => $entity->id(),
      ]);
    }
  }

}
