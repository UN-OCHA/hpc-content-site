<?php

namespace Drupal\ncms_paragraphs\Traits;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Helper trait for paragraphs.
 */
trait ParagraphHelperTrait {

  /**
   * Get parent revision which actually refrences a paragraph.
   *
   * Paragraph parent references do not include the parent revision, so we may
   * need to check multiple parent revisions to find the one which actually
   * references the specific revision of the passed in paragraph.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The top node.
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   A paragraph.
   * @param string|null $langcode
   *   The language to load, default to the same as the paragraph.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The parent entity revision referencing $paragraph, or NULL if not found.
   */
  protected function getParagraphParentRevision(NodeInterface $node, ParagraphInterface $paragraph, ?string $langcode = NULL): ?ContentEntityInterface {
    // Try the latest parent revision.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $parent_storage */
    $parent_storage = self::entityTypeManager()->getStorage($paragraph->get('parent_type')->value);
    assert($parent_storage instanceof RevisionableStorageInterface, 'Can only handle revisionable entities.');
    $latest_parent_revision = $parent_storage->getLatestRevisionId($paragraph->get('parent_id')->value);
    $parent = $parent_storage->loadRevision($latest_parent_revision);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
    $current_paragraph_delta = $this->getParagraphDelta($paragraph, $parent);

    // Try the default parent revision.
    if ($current_paragraph_delta === FALSE) {
      $parent = $paragraph->getParentEntity();
      $current_paragraph_delta = $this->getParagraphDelta($paragraph, $parent);
    }

    // Brute force fallback.
    if ($current_paragraph_delta === FALSE) {
      // Sanity checks.
      while ($parent instanceof ParagraphInterface) {
        $parent = $parent->getParentEntity();
      }
      if (!$parent instanceof NodeInterface || $parent->id() !== $node->id()) {
        throw new \LogicException('The passed in node is not the parent of the paragraph.');
      }
      // We may have a broken node structure if it comes to this, but at
      // least we'll be able to edit it and preserve the structure.
      $tree = $this->getFlatParagraphTree($node);
      $paragraph_data = $tree[$paragraph->id()] ?? NULL;
      if ($paragraph_data
        && $paragraph_data['revision'] === $paragraph->getRevisionId()
        && $paragraph_data['parentType'] === $paragraph->get('parent_type')->value
        && $paragraph_data['parentId'] === $paragraph->get('parent_id')->value
      ) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface|null $parent */
        $parent = $parent_storage->loadRevision($paragraph_data['parentRevision']);
        $current_paragraph_delta = $parent instanceof ContentEntityInterface ? $this->getParagraphDelta($paragraph, $parent) : FALSE;
      }
    }

    return $current_paragraph_delta !== FALSE ? $parent : NULL;
  }

  /**
   * Get at which delta in the parent field a paragraph is referenced.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   A paragraph.
   * @param \Drupal\Core\Entity\ContentEntityInterface $parent
   *   The parent element.
   *
   * @return false|int|string
   *   The delta as an int/string or FALSE if not found.
   */
  protected function getParagraphDelta(ParagraphInterface $paragraph, ContentEntityInterface $parent) {
    $field_items = $parent->get($paragraph->parent_field_name->value);
    foreach ($field_items as $delta => $item) {
      // Explicitly compare ids first to avoid loading a new instance of the
      // referenced revision, which gives a clone since they are not cached in
      // storage, but may be cached on the parent's reference field item.
      if (
        (
          !$paragraph->isNew()
          && (
            $item->target_revision_id === $paragraph->getRevisionId()
            && $item->target_id = $paragraph->id()
          )
        ) || (
          $paragraph->isNew()
          && !isset($item->target_revision_id)
          && $item->entity
          && $item->entity === $paragraph
        )
      ) {
        return $delta;
      }
    }
    return FALSE;
  }

  /**
   * Get a flat-tree representation of the paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $parent
   *   The parent entity.
   * @param array $flat_tree
   *   The flat tree array.
   *
   * @return array
   *   The flat tree representation of the paragraphs attached to the parent
   *   entity.
   */
  protected function getFlatParagraphTree(EntityInterface $parent, &$flat_tree = []) {
    if (!$parent->hasField('field_paragraphs')) {
      return $flat_tree;
    }
    $paragraphs = $parent->get('field_paragraphs')->referencedEntities();

    foreach ($paragraphs as $paragraph) {
      $flat_tree[$paragraph->id()] = [
        'revision' => $paragraph->getRevisionId(),
        'parentType' => $paragraph->get('parent_type')->value,
        'parentId' => $paragraph->get('parent_id')->value,
        'parentRevision' => $parent instanceof RevisionableInterface ? $parent->getRevisionId() : NULL,
      ];

      // Recursively call the function for nested paragraphs.
      $this->getFlatParagraphTree($paragraph, $flat_tree);
    }

    return $flat_tree;
  }

}
