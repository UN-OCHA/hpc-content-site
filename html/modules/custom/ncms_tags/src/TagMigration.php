<?php

namespace Drupal\ncms_tags;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Service class for tag migration.
 */
class TagMigration {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The common taxonomies service.
   *
   * @var \Drupal\ncms_tags\CommonTaxonomyService
   */
  protected $commonTaxonomies;

  /**
   * TagMigration constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\ncms_tags\CommonTaxonomyService $common_taxonomies
   *   The common taxonomies service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CommonTaxonomyService $common_taxonomies) {
    $this->entityTypeManager = $entity_type_manager;
    $this->commonTaxonomies = $common_taxonomies;
  }

  /**
   * Create a term.
   *
   * @param string $vid
   *   The machine name of the vocabulary.
   * @param string $term_name
   *   The term name.
   * @param int $weight
   *   The weight of the term in the vocabulary.
   * @param \Drupal\taxonomy\TermInterface $parent_term
   *   An optional parent term object.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term.
   */
  public function createTag($vid, $term_name, $weight = NULL, TermInterface $parent_term = NULL) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties(array_filter([
      'vid' => $vid,
      'name' => $term_name,
      'weight' => $weight,
      'parent' => $parent_term?->id(),
    ]));
    if (!empty($terms)) {
      return reset($terms);
    }
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = $term_storage->create(array_filter([
      'vid' => $vid,
      'name' => $term_name,
      'weight' => $weight,
      'parent' => $parent_term?->id(),
    ]));
    $term->save();
    return $term;
  }

  /**
   * Migrate the given term into field name.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term object to migrate.
   * @param string[] $alternative_names
   *   An optional list of alternative names for the given term.
   * @param bool $cleanup_tags
   *   Whether the tags should be cleaned up after migration.
   *
   * @return bool
   *   The result state of the operation.
   */
  public function migrateTag(TermInterface $term, $alternative_names = [], $cleanup_tags = TRUE) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    /** @var \Drupal\taxonomy\TermInterface $tag */
    $tags = $term_storage->loadByProperties([
      'vid' => 'major_tags',
      'name' => array_merge([$term->getName()], $alternative_names),
    ]);
    if (empty($tags)) {
      return FALSE;
    }
    $this->migrateTermReferences($tags, [$term]);

    if ($cleanup_tags) {
      foreach ($tags as $tag) {
        $tag->delete();
      }
    }
    return TRUE;
  }

  /**
   * Migrate term references for the given source tags.
   *
   * @param \Drupal\taxonomy\TermInterface[] $source_tags
   *   The term objects to migrate.
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The target term object that the field should reference.
   */
  public function migrateTermReferences(array $source_tags, array $terms) {
    $tag_ids = array_map(function (TermInterface $_term) {
      return $_term->id();
    }, $source_tags);

    $this->migrateNodeTerms($tag_ids, $terms);
    $this->migrateParagraphTerms($tag_ids, $terms);
    $this->migrateContentSpaceTerms($tag_ids, $terms);
  }

  /**
   * Migrate node terms from the deprecated free tag field to a dedicated one.
   *
   * @param int[] $tag_ids
   *   An array of tag ids.
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The terms to migrate to.
   */
  private function migrateNodeTerms(array $tag_ids, array $terms) {
    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $node_storage->loadByProperties([
      'type' => ['article', 'document'],
      'field_tags' => $tag_ids,
    ]);
    foreach ($nodes as $node) {
      $this->removeTagIdsFromField($node, 'field_tags', $tag_ids);
      $this->addTermsToField($node, $terms);
      $this->saveEntity($node);

      $revision_ids = $node_storage->revisionIds($node);
      /** @var \Drupal\node\NodeInterface[] $revisions */
      $revisions = $node_storage->loadMultipleRevisions($revision_ids);
      foreach ($revisions as $revision) {
        $this->removeTagIdsFromField($revision, 'field_tags', $tag_ids);
        $this->addTermsToField($revision, $terms);
        $this->saveEntity($revision);
      }
    }
  }

  /**
   * Migrate paragraph terms from the deprecated free tag field.
   *
   * @param int[] $tag_ids
   *   An array of tag ids.
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The terms to migrate to.
   */
  private function migrateParagraphTerms(array $tag_ids, array $terms) {
    $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
    /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
    $paragraphs = $paragraph_storage->loadByProperties([
      'type' => ['document_chapter'],
      'field_tags' => $tag_ids,
    ]);
    foreach ($paragraphs as $paragraph) {
      $this->removeTagIdsFromField($paragraph, 'field_tags', $tag_ids);
      $this->addTermsToField($paragraph, $terms);
      $this->saveEntity($paragraph);
    }
  }

  /**
   * Migrate content space terms from the deprecated free tag field.
   *
   * @param int[] $tag_ids
   *   An array of tag ids.
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The terms to migrate to.
   */
  private function migrateContentSpaceTerms(array $tag_ids, array $terms) {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    /** @var \Drupal\taxonomy\TermInterface[] $content_spaces */
    $content_spaces = $term_storage->loadByProperties([
      'vid' => ['content_space'],
      'field_major_tags' => $tag_ids,
    ]);
    foreach ($content_spaces as $content_space) {
      $this->removeTagIdsFromField($content_space, 'field_major_tags', $tag_ids);
      $this->addTermsToField($content_space, $terms);
      $this->saveEntity($content_space);
    }
  }

  /**
   * Remove the given tag ids from an entity field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param int[] $tag_ids
   *   The tag ids to remove.
   */
  private function removeTagIdsFromField(ContentEntityInterface $entity, $field_name, array $tag_ids) {
    $tags = $entity->get($field_name)->getValue();
    $tags = array_filter($tags, function ($_tag) use ($tag_ids) {
      return !in_array($_tag['target_id'], $tag_ids);
    });
    $entity->get($field_name)->setValue($tags);
  }

  /**
   * Add the given terms to an entity field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\taxonomy\TermInterface[] $terms
   *   The terms to add.
   */
  private function addTermsToField(ContentEntityInterface $entity, array $terms) {
    foreach ($terms as $term) {
      $field_name = $this->commonTaxonomies->getFieldNameForTaxonomyBundle($term->bundle());
      if (!$entity->hasField($field_name)) {
        return;
      }
      $field_tags = $entity->get($field_name)->getValue();
      $field_tags = array_filter($field_tags, function ($_tag) use ($term) {
        return $_tag['target_id'] != $term->id();
      });
      $field_tags[] = ['target_id' => $term->id()];
      $entity->get($field_name)->setValue($field_tags);
    }
  }

  /**
   * Save the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to save.
   */
  private function saveEntity(ContentEntityInterface $entity) {
    $entity->setNewRevision(FALSE);
    $entity->setSyncing(TRUE);
    $entity->save();
  }

}
