<?php

namespace Drupal\ncms_gho_import;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;

/**
 * Helper class for content import.
 */
class ContentImportManager {

  /**
   * A list of paragraph types that can be safely removed.
   */
  private const OBSOLETE_PARAGRAPH_TYPES = [
    'needs_and_requirements',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Public constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Set the content space for the imported content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   */
  public function setContentSpace(ContentEntityInterface &$entity) {
    if (!$entity instanceof ContentSpaceAwareInterface) {
      return;
    }
    $content_space = $this->loadTermByName('Global', 'content_space');
    if (!$content_space) {
      return;
    }
    $entity->get('field_content_space')->setValue([
      // Global content space.
      'target_id' => $content_space->id(),
    ]);
  }

  /**
   * Set some properties for the imported content.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to be created.
   */
  public function setContentProperties(ContentEntityInterface &$entity) {
    if (!$entity instanceof ContentBase) {
      return;
    }
    $entity->get('field_short_title')->setValue($entity->label());
    if ($document_type = $this->loadTermByName('GHO', 'document_type')) {
      $entity->get('field_document_type')->setValue(['target_id' => $document_type->id()]);
    }
    if ($year = $this->loadTermByName('2021', 'year')) {
      $entity->get('field_year')->setValue(['target_id' => $year->id()]);
    }
  }

  /**
   * Remove obsolete paragraph types from the incoming content array.
   *
   * @param array $content
   *   An array containing the content.
   */
  public function removeObsoleteParagraphs(array &$content) {
    if (empty($content['custom_fields']['field_paragraphs'])) {
      return;
    }
    foreach ($content['custom_fields']['field_paragraphs'] as $key => $item) {
      if (in_array($item['bundle'], self::OBSOLETE_PARAGRAPH_TYPES)) {
        unset($content['custom_fields']['field_paragraphs'][$key]);
        continue;
      }
      if ($item['bundle'] == 'sub_article') {
        foreach ($item['custom_fields']['field_article'] as &$article) {
          $this->removeObsoleteParagraphs($article);
        }
      }
    }
  }

  /**
   * Load a term by name and vocabulary.
   *
   * @param string $name
   *   The name of the term.
   * @param string $vid
   *   The vocabulary id.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   A taxonomy term object or NULL.
   */
  private function loadTermByName($name, $vid) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => $vid,
      'name' => $name,
    ]);
    return count($terms) == 1 ? reset($terms) : NULL;
  }

}
