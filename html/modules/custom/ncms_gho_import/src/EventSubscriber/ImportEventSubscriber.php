<?php

namespace Drupal\ncms_gho_import\EventSubscriber;

use Drupal\ncms_gho_import\ContentImportManager;
use Drupal\single_content_sync\Event\ImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to react to import events.
 */
class ImportEventSubscriber implements EventSubscriberInterface {

  /**
   * Our custom content import manager.
   *
   * @var \Drupal\ncms_gho_import\ContentImportManager
   */
  private $contentImportManager;

  /**
   * Public constructor.
   *
   * @param \Drupal\ncms_gho_import\ContentImportManager $content_import_manager
   *   The content import manager.
   */
  public function __construct(ContentImportManager $content_import_manager) {
    $this->contentImportManager = $content_import_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ImportEvent::class] = ['onImport'];
    return $events;
  }

  /**
   * React to content being imported.
   *
   * @param \Drupal\single_content_sync\Event\ImportEvent $event
   *   The import event.
   */
  public function onImport(ImportEvent $event) {
    $content = $event->getContent();
    $entity = $event->getEntity();

    $this->contentImportManager->setContentSpace($entity);
    $this->contentImportManager->setContentProperties($entity);

    $this->contentImportManager->removeObsoleteParagraphs($content, $entity);

    $event->setContent($content);
    $event->setEntity($entity);
  }

}
