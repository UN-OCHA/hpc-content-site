<?php

namespace Drupal\ncms_ui\Entity\Media;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_usage\EntityUsageListTrait;
use Drupal\media\Entity\Media;
use Drupal\ncms_paragraphs\Traits\ParagraphHelperTrait;
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
   * Get the file url generator service.
   *
   * @return \Drupal\Core\File\FileUrlGenerator
   *   The file url generator service.
   */
  private static function filUrlGenerator() {
    return \Drupal::service('file_url_generator');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityOperations() {
    $operations = [];
    if (!$this->isDeleted()) {
      $operations['usage'] = [
        'title' => $this->t('Places used'),
        'url' => Url::fromRoute('view.media_usage.page_content', [
          'media' => $this->id(),
        ]),
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
   * Check if the media is used in mandatory fields.
   *
   * @return bool
   *   TRUE if the media is used in mandatory fields, FALSE otherwise.
   */
  public function hasMandatoryReferences(): bool {
    $references = $this->getUsageReferences();
    return !empty($references['mandatory']);
  }

  /**
   * Check if the media is used in optional fields.
   *
   * @return bool
   *   TRUE if the media is used in optional fields, FALSE otherwise.
   */
  public function hasOptionalReferences(): bool {
    $references = $this->getUsageReferences();
    return !empty($references['optional']);
  }

  /**
   * Get a list of usage references for this media.
   *
   * @return array
   *   An array with the keys 'mandatory' and 'optional'.
   */
  public function getUsageReferences(?array $entity_type_ids = NULL): array {
    $entity_usage = $this->entityUsage();
    $sources_list = $entity_usage->listSources($this);

    $references = [
      'mandatory' => [],
      'optional' => [],
    ];
    foreach ($sources_list as $entity_type_id => $entity_sources) {
      foreach ($entity_sources as $entity_id => $sources) {
        $entity = $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
        if (is_array($entity_type_ids) && !in_array($entity->getEntityTypeId(), $entity_type_ids)) {
          continue;
        }
        foreach ($sources as $source) {
          if ($source['source_langcode'] != 'en') {
            // Let's not look at content in other languages for now.
            continue;
          }
          if ($entity instanceof NodeInterface && $entity->language() == 'en' && $entity->isDefaultRevision() && $entity->getRevisionId() == $source['source_vid'] && $source['field_name'] == 'field_hero_image') {
            $source['source_id'] = $entity->id();
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
            $source['parent_id'] = $parent_revision->id();
            $source['parent_revision_id'] = $parent_revision->getRevisionId();
            if (array_key_exists($entity->bundle(), self::PARAGRAPHS_WITH_MANDATORY_IMAGES) && in_array($source['field_name'], self::PARAGRAPHS_WITH_MANDATORY_IMAGES[$entity->bundle()])) {
              $references['mandatory'][] = $source;
            }
            if (array_key_exists($entity->bundle(), self::PARAGRAPHS_WITH_OPTIONAL_IMAGES) && in_array($source['field_name'], self::PARAGRAPHS_WITH_OPTIONAL_IMAGES[$entity->bundle()])) {
              $references['optional'][] = $source;
            }
          }
        }
      }
    }
    return $references;
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
    $this->isDefaultRevision(TRUE);
    $this->setNewRevision(TRUE);
    $this->setRevisionTranslationAffectedEnforced(TRUE);
    $this->setModerationState('trash');
  }

}
