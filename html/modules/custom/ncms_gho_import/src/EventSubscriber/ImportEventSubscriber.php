<?php

namespace Drupal\ncms_gho_import\EventSubscriber;

use Drupal\ncms_gho_import\ContentImportManager;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\single_content_sync\ContentImporter;
use Drupal\single_content_sync\Event\ImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to react to import events.
 */
class ImportEventSubscriber implements EventSubscriberInterface {

  /**
   * The content importer from the Single Content Sync module.
   *
   * @var \Drupal\single_content_sync\ContentImporter
   */
  private $contentImporter;

  /**
   * Our custom content import manager.
   *
   * @var \Drupal\ncms_gho_import\ContentImportManager
   */
  private $contentImportManager;

  /**
   * Public constructor.
   *
   * @param \Drupal\single_content_sync\ContentImporter $content_importer
   *   The content importer.
   * @param \Drupal\ncms_gho_import\ContentImportManager $content_import_manager
   *   The content import manager.
   */
  public function __construct(ContentImporter $content_importer, ContentImportManager $content_import_manager) {
    $this->contentImporter = $content_importer;
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

    $entity->setNewRevision(FALSE);
    $entity->setSyncing(TRUE);

    $this->contentImporter->importBaseValues($entity, $content['base_fields']);
    $entity->save();

    // Import base data.
    if ($entity instanceof ContentInterface) {
      $this->contentImportManager->setContentSpace($entity);
      $this->contentImportManager->setContentProperties($entity, $content);
      $this->contentImportManager->setContentTags($entity);

      // Import hero image.
      $this->contentImportManager->updateHeroImage($content);

      // Import paragraphs.
      $this->contentImportManager->updateParagraphs($content);

      // Remove obsolete paragraphs.
      $this->contentImportManager->removeObsoleteParagraphs($content);
    }

    if ($entity instanceof Article) {
      $this->contentImportManager->addArticleToDocument($entity, $content);

      if ($entity->label() == 'Global Humanitarian Overview 2021') {
        $this->contentImportManager->importDocumentImage($content);
      }
    }

    // Don't create menu items.
    unset($content['base_fields']['menu_link']);

    // Don't process translations.
    unset($content['translations']);

    $event->setContent($content);
    $event->setEntity($entity);
  }

}
