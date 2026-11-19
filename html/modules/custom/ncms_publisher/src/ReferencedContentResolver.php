<?php

namespace Drupal\ncms_publisher;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ncms_ui\Entity\ContentInterface;

/**
 * Resolves published content that depends on another content entity.
 */
class ReferencedContentResolver {

  /**
   * Entity types that can connect media to exported content.
   */
  private const SOURCE_ENTITY_TYPES = [
    'node',
    'paragraph',
  ];

  /**
   * Field types that store entity references.
   */
  private const ENTITY_REFERENCE_FIELD_TYPES = [
    'entity_reference',
    'entity_reference_revisions',
  ];

  /**
   * Field types that can contain embedded media markup.
   */
  private const TEXT_FIELD_TYPES = [
    'string_long',
    'text',
    'text_long',
    'text_with_summary',
  ];

  /**
   * Constructs a referenced content resolver.
   */
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager, private readonly EntityFieldManagerInterface $entityFieldManager) {
  }

  /**
   * Resolve published articles and documents that reference an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $target
   *   The referenced entity.
   *
   * @return \Drupal\ncms_ui\Entity\ContentInterface[]
   *   Published content keyed by entity id.
   */
  public function resolve(ContentEntityInterface $target): array {
    $content = [];
    $pending = [$target];
    $visited = [];

    while ($pending) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = array_pop($pending);
      $key = $entity->getEntityTypeId() . ':' . $entity->id();
      if (isset($visited[$key])) {
        continue;
      }
      $visited[$key] = TRUE;

      foreach ($this->loadReferencingEntities($entity) as $source) {
        if ($source instanceof ContentInterface && in_array($source->bundle(), ['article', 'document'], TRUE) && $source->isPublished()) {
          $content[$source->id()] = $source;
        }
        if ($source instanceof ContentEntityInterface) {
          // Continue through paragraph parents and embedded content so media
          // used by nested content refreshes every affected export.
          $pending[] = $source;
        }
      }
    }

    return $content;
  }

  /**
   * Load node and paragraph entities that reference the target entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $target
   *   The referenced entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Referencing entities keyed by entity type and id.
   */
  private function loadReferencingEntities(ContentEntityInterface $target): array {
    $sources = [];
    // Content can itself be rendered by a paragraph, such as a sub-article.
    // Direct node-to-node fields are bookkeeping relationships and must not
    // spread a media refresh across every related article and document.
    $source_entity_type_ids = $target instanceof ContentInterface ? ['paragraph'] : self::SOURCE_ENTITY_TYPES;
    foreach (self::ENTITY_REFERENCE_FIELD_TYPES as $field_type) {
      $field_map = $this->entityFieldManager->getFieldMapByFieldType($field_type);
      foreach ($source_entity_type_ids as $source_entity_type_id) {
        foreach (array_keys($field_map[$source_entity_type_id] ?? []) as $field_name) {
          $field_storage = $this->entityFieldManager->getFieldStorageDefinitions($source_entity_type_id)[$field_name] ?? NULL;
          if (!$field_storage || $field_storage->getSetting('target_type') !== $target->getEntityTypeId()) {
            continue;
          }
          $storage = $this->entityTypeManager->getStorage($source_entity_type_id);
          foreach ($storage->loadByProperties([$field_name => $target->id()]) as $source) {
            $sources[$source->getEntityTypeId() . ':' . $source->id()] = $source;
          }
        }
      }
    }

    if ($target->getEntityTypeId() === 'media') {
      $sources += $this->loadMediaEmbedSources($target);
    }
    return $sources;
  }

  /**
   * Load node and paragraph entities containing a rich-text media embed.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $media
   *   The media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Referencing entities keyed by entity type and id.
   */
  private function loadMediaEmbedSources(ContentEntityInterface $media): array {
    $sources = [];
    foreach (self::TEXT_FIELD_TYPES as $field_type) {
      $field_map = $this->entityFieldManager->getFieldMapByFieldType($field_type);
      foreach (self::SOURCE_ENTITY_TYPES as $source_entity_type_id) {
        foreach (array_keys($field_map[$source_entity_type_id] ?? []) as $field_name) {
          $storage = $this->entityTypeManager->getStorage($source_entity_type_id);
          $ids = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition($field_name . '.value', $media->uuid(), 'CONTAINS')
            ->execute();
          foreach ($storage->loadMultiple($ids) as $source) {
            $sources[$source->getEntityTypeId() . ':' . $source->id()] = $source;
          }
        }
      }
    }
    return $sources;
  }

}
