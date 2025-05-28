<?php

namespace Drupal\ncms_ui\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\replicate\Events\ReplicateAlterEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter replicated entities.
 */
class ReplicateEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content manager.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentSpaceManager $content_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSpaceManager = $content_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::REPLICATE_ALTER] = ['setContentSpace'];
    return $events;
  }

  /**
   * Set the content space field for the replicated entity.
   *
   * Make sure that entities are always replicated into the currently active
   * content space.
   *
   * @param \Drupal\replicate\Events\ReplicateAlterEvent $event
   *   The ReplicateAlterEvent event.
   */
  public function setContentSpace(ReplicateAlterEvent $event) {
    $entity = $event->getEntity();
    $content_space_id = $this->contentSpaceManager->getCurrentContentSpaceId();
    if (!$entity instanceof ContentSpaceAwareInterface || empty($content_space_id)) {
      return;
    }
    // Set to the current one.
    $entity->setContentSpace($content_space_id);
  }

}
