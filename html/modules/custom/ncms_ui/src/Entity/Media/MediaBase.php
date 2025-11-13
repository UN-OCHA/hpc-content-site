<?php

namespace Drupal\ncms_ui\Entity\Media;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_usage\EntityUsageListTrait;
use Drupal\media\Entity\Media;
use Drupal\ncms_paragraphs\Traits\ParagraphHelperTrait;
use Drupal\ncms_ui\Entity\BaseEntityInterface;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\MediaInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;
use Drupal\ncms_ui\Traits\EntityBundleLabelTrait;
use Drupal\ncms_ui\Traits\ModalLinkTrait;
use Drupal\ncms_ui\Traits\ModeratedEntityTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Bundle base class for media entities.
 */
abstract class MediaBase extends Media implements MediaInterface {

  use ContentSpaceEntityTrait;
  use EntityBundleLabelTrait;
  use ModeratedEntityTrait;
  use ModalLinkTrait;
  use EntityUsageListTrait;
  use ParagraphHelperTrait;

  const PARAGRAPHS_WITH_MANDATORY_IMAGES = [
    'infographic' => ['field_media_infographic'],
    'image_with_text' => ['field_image'],
    'photo_gallery' => ['field_photos'],
    // 'facts_and_figures', USES IMAGE WITH TEXT
  ];
  const PARAGRAPHS_WITH_OPTIONAL_IMAGES = [
    'interactive_content' => ['field_image'],
  ];

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'view' && (!$this->isDeleted() || $this->hasContentSpaceAccess($account))) {
      // Always allow view operation on specific internal routes for non
      // deleted content or if the user can access the content space.
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }

    // These operations are allowed when an entity is marked as deleted.
    $delete_operations = [
      'restore',
      'delete',
    ];
    if (in_array($operation, $delete_operations) && $this->hasContentSpaceAccess($account)) {
      $result = AccessResult::allowedIf($this->isDeleted());
      return $return_as_object ? $result : $result->isAllowed();
    }
    elseif ($this->isDeleted()) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }

    // These operations are used in ncms_ui.routing.yml and should be mapped to
    // the 'update' operation.
    $update_operations = [
      'publish revision',
      'unpublish revision',
      'soft delete',
      'restore',
      'version history',
    ];
    if (in_array($operation, $update_operations)) {
      $operation = 'update';
    }
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel == 'soft-delete-form' && $this->access('soft delete')) {
      return Url::fromRoute('entity.media.soft_delete', [
        'media' => $this->id(),
      ], $options);
    }
    if ($rel == 'restore-form' && $this->access('restore')) {
      return Url::fromRoute('entity.media.restore', [
        'media' => $this->id(),
      ], $options);
    }
    if ($rel == 'canonical' && !$this->access('update') && !$this->isDeleted()) {
      // The canonical url for media entities is the edit url. In cases where
      // access to the edit form is forbidden, we need to use a different url
      // here, so we use the actual image url.
      $thumbnail_uri = $this->getThumbnailUri(FALSE);
      $path = self::filUrlGenerator()->generateAbsoluteString($thumbnail_uri);
      return Url::fromUri($path);
    }
    if ($rel == 'places-used' && $this->access('update')) {
      $node_references = $this->getUsageReferences(['node']);
      $paragraph_references = $this->getUsageReferences(['paragraph']);
      $has_node_references = !empty($node_references['optional']) || !empty(!empty($node_references['mandatory']));
      $has_paragraph_references = !empty($paragraph_references['optional']) || !empty(!empty($paragraph_references['mandatory']));
      $display_id = !$has_node_references && $has_paragraph_references ? 'page_paragraphs' : 'page_content';
      return Url::fromRoute('view.media_usage.' . $display_id, [
        'media' => $this->id(),
      ]);
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content/media');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityOperations() {
    $operations = [];
    if (!$this->isDeleted() && $this->access('update')) {
      $operations['usage'] = [
        'title' => $this->t('Places used'),
        'url' => $this->toUrl('places-used'),
        'weight' => 50,
      ];
    }
    if ($this->access('soft delete')) {
      $operations['soft_delete'] = [
        'title' => $this->t('Move to trash'),
        'url' => $this->toUrl('soft-delete-form', $this->getModalUrlOptions($this->t('Confirm delete'))),
        'weight' => 50,
      ];
    }
    if ($this->access('restore')) {
      $operations['restore'] = [
        'title' => $this->t('Restore'),
        'url' => $this->toUrl('restore-form', $this->getModalUrlOptions($this->t('Confirm restore'))),
        'weight' => 50,
      ];
    }
    if ($this->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete for ever'),
        'url' => $this->toUrl('delete-form', $this->getModalUrlOptions($this->t('Confirm delete'))),
        'weight' => 50,
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    if ($this->isNew()) {
      return FALSE;
    }
    return $this->getLatestRevision()?->isModerationState('trash') ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMandatoryReferences(): bool {
    $references = $this->getUsageReferences();
    return !empty($references['mandatory']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasOptionalReferences(): bool {
    $references = $this->getUsageReferences();
    return !empty($references['optional']);
  }

  /**
   * {@inheritdoc}
   */
  public function getNodesAffectedByDeletion() {
    $references = $this->getUsageReferences();
    $nodes = [];
    // Only looking at the optional references, because we can't delete media
    // with active mandatory references.
    foreach ($references['optional'] as $source) {
      $entity = $this->entityTypeManager()->getStorage($source['source_type'])->load($source['source_id']);
      if ($entity instanceof ParagraphInterface) {
        $entity = $entity->getParentEntity();
      }
      if (!$entity instanceof NodeInterface) {
        continue;
      }
      $nodes[] = $entity;
    }
    return $nodes;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageCount(?array $entity_type_ids = NULL): int {
    $references = $this->getUsageReferences($entity_type_ids);
    return count($references['mandatory']) + count($references['optional']);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsageReferences(?array $entity_type_ids = NULL): array {
    $entity_usage = $this->entityUsage();
    $sources = $entity_usage->listSources($this, FALSE);
    $references = [
      'mandatory' => [],
      'optional' => [],
    ];
    if ($this->isDeleted()) {
      return $references;
    }
    foreach ($sources as $source) {
      if (is_array($entity_type_ids) && !in_array($source['source_type'], $entity_type_ids)) {
        // Not of the type requested.
        continue;
      }
      if ($source['source_langcode'] != 'en') {
        // Let's not look at content in other languages for now.
        continue;
      }
      $entity = $this->entityTypeManager()->getStorage($source['source_type'])->load($source['source_id']);
      if ($entity instanceof NodeInterface && $entity->language()->getId() == 'en' && $entity->isDefaultRevision() && $entity->getRevisionId() == $source['source_vid']) {
        // This is the default revision of a content node in the default
        // language.
        $source['source_id'] = $entity->id();
        $source['cache_tags'] = $entity->getCacheTagsToInvalidate();
        $references['optional'][] = $source;
      }
      if ($entity instanceof ParagraphInterface && $entity->getRevisionId() == $source['source_vid']) {
        $parent = $entity->getParentEntity();
        while ($parent instanceof ParagraphInterface) {
          $parent = $parent->getParentEntity();
        }
        $parent_revision = $this->getParagraphParentRevision($parent, $entity);
        if (!$parent_revision || !$parent_revision instanceof ContentInterface) {
          continue;
        }
        if ($parent_revision->isDeleted()) {
          continue;
        }
        if (!$parent_revision->isDefaultRevision()) {
          continue;
        }
        $source['source_id'] = $entity->id();
        $source['parent_id'] = $parent_revision->id();
        $source['parent_revision_id'] = $parent_revision->getRevisionId();
        $source['cache_tags'] = $parent_revision->getCacheTagsToInvalidate();
        if (array_key_exists($entity->bundle(), self::PARAGRAPHS_WITH_MANDATORY_IMAGES) && in_array($source['field_name'], self::PARAGRAPHS_WITH_MANDATORY_IMAGES[$entity->bundle()])) {
          $references['mandatory'][] = $source;
        }
        if (array_key_exists($entity->bundle(), self::PARAGRAPHS_WITH_OPTIONAL_IMAGES) && in_array($source['field_name'], self::PARAGRAPHS_WITH_OPTIONAL_IMAGES[$entity->bundle()])) {
          $references['optional'][] = $source;
        }
      }
    }
    return $references;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequiredFor(EntityInterface $entity): bool {
    $references = $this->getUsageReferences();
    if (!$entity instanceof ContentInterface && !$entity instanceof ParagraphInterface) {
      return FALSE;
    }
    foreach ($references['mandatory'] as $reference) {
      if (($reference['source_id'] ?? '') === $entity->id()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision() {
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('media');
    $revision_id = $node_storage->getLatestRevisionId($this->id());
    return $revision_id ? $node_storage->loadRevision($revision_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isModerationState($state) {
    return $this->getModerationState() == $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationState() {
    return $this->moderation_state->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setModerationState($state) {
    $this->moderation_state->value = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeleted() {
    parent::setUnpublished();

    if ($this->hasMandatoryReferences()) {
      throw new \Exception('Cannot delete media entity with mandatory references.');
    }

    $affected_nodes = [];
    $this->updateNodesWithOptionalUsages($affected_nodes);
    $this->updateParagraphsWithOptionalUsages($affected_nodes);
    $this->createNewNodeRevisions($affected_nodes, $this->t('Moved media %label to trash.', ['%label' => $this->label()]));

    $this->isDefaultRevision(TRUE);
    $this->setNewRevision(TRUE);
    $this->setRevisionTranslationAffectedEnforced(TRUE);
    $this->setModerationState('trash');

    // Invalidate caches so that changes are applied immediately.
    Cache::invalidateTags($this->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function restore() {
    $this->getEntityStorage()->deleteLatestRevision($this);

    $affected_nodes = [];
    $this->updateNodesWithOptionalUsages($affected_nodes);
    $this->createNewNodeRevisions($affected_nodes, $this->t('Restored media %label.', ['%label' => $this->label()]));

    // Invalidate caches so that changes are applied immediately.
    $cache_tags = $this->getCacheTagsToInvalidate();
    if ($this instanceof MediaBase) {
      $usages = $this->getUsageReferences(['node']);
      foreach (array_merge($usages['mandatory'], $usages['optional']) as $usage) {
        $cache_tags = Cache::mergeTags($cache_tags, $usage['cache_tags']);
      }
    }
    Cache::invalidateTags($cache_tags);
  }

  /**
   * Create new revisions for the given nodes.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentInterface[] $nodes
   *   Nodes to create new revisions for.
   * @param \Drupal\Component\Render\MarkupInterface|string $log_message
   *   The log message to set.
   */
  private function createNewNodeRevisions(array $nodes, MarkupInterface|string $log_message) {
    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($nodes as $node) {
      // Unpublish previous published version.
      if ($node instanceof BaseEntityInterface && $last_published = $node->getLastPublishedRevision()) {
        $node_storage->updateRevisionStatus($last_published, NodeInterface::NOT_PUBLISHED);
      }

      // And create a new published version.
      $node->isDefaultRevision(TRUE);
      $node->setNewRevision(TRUE);
      $node->setRevisionUserId(self::getDefaultEntityOwner());
      $node->setRevisionLogMessage($log_message);
      $node->setRevisionTranslationAffectedEnforced(TRUE);
      $node->save();
    }
  }

  /**
   * Update nodes that have optional usages of this media item.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentInterface[] $affected_nodes
   *   Storage for nodes affected by removing the paragraph.
   */
  private function updateNodesWithOptionalUsages(array &$affected_nodes): void {
    // Find the usages of this directly on the node level, so we can remove the
    // reference to the media and create updated versions of the content to
    // inform publishers about the media having been removed.
    $node_usages = $this->getUsageReferences(['node']);
    foreach ($node_usages['optional'] as $node_source) {
      $node = $this->entityTypeManager()->getStorage('node')->load($node_source['source_id']);
      if (!$node instanceof ContentInterface || !$node->hasField($node_source['field_name'])) {
        continue;
      }
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
      $field = $node->get($node_source['field_name']);
      $field->filter(function (EntityReferenceItem $item) {
        $value = $item->getValue();
        return $value['target_id'] !== $this->id();
      });
      $affected_nodes[$node->id()] = $node;
    }
  }

  /**
   * Update paragraphs that have optional usages of this media item.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentInterface[] $affected_nodes
   *   Storage for nodes affected by removing the paragraph.
   */
  private function updateParagraphsWithOptionalUsages(array &$affected_nodes): void {
    $paragraph_usages = $this->getUsageReferences(['paragraph']);
    foreach ($paragraph_usages['optional'] as $paragraph_source) {
      $paragraph = $this->entityTypeManager()->getStorage('paragraph')->load($paragraph_source['source_id']);
      if (!$paragraph instanceof ParagraphInterface || !$paragraph->hasField($paragraph_source['field_name'])) {
        continue;
      }
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $field */
      $field = $paragraph->get($paragraph_source['field_name']);
      $field->filter(function (EntityReferenceItem $item) {
        $value = $item->getValue();
        return $value['target_id'] !== $this->id();
      });
      $paragraph->save();
      $parent = $paragraph->getParentEntity();
      $affected_nodes[$parent->id()] = $parent;
    }
  }

  /**
   * Get the file url generator service.
   *
   * @return \Drupal\Core\File\FileUrlGenerator
   *   The file url generator service.
   */
  private static function filUrlGenerator() {
    return \Drupal::service('file_url_generator');
  }

}
