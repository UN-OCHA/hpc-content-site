<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Entity\EntityOverviewInterface;
use Drupal\ncms_ui\Entity\IframeDisplayContentInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;
use Drupal\ncms_ui\Traits\IframeDisplayContentTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Bundle class for organization nodes.
 */
abstract class ContentBase extends Node implements ContentInterface, ContentSpaceAwareInterface, ContentVersionInterface, EntityOverviewInterface, IframeDisplayContentInterface {

  use StringTranslationTrait;
  use ContentSpaceEntityTrait;
  use IframeDisplayContentTrait;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $route_match = $this->getRouteMatch();
    $route_name = $route_match?->getRouteName() ?? NULL;
    $grant_routes = [
      'entity.node.standalone',
      'entity.node.iframe',
    ];
    if (in_array($route_name, $grant_routes) && $operation == 'view' && (!$this->isDeleted() || $this->hasContentSpaceAccess($account))) {
      // Always allow view operation on specific internal routes for non
      // deleted content or if the user can access the content space.
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }

    // These operations are allowed when a node is marked as deleted.
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
  abstract public function getOverviewUrl();

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    return $this->type->entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished() {
    parent::setPublished();
    $this->moderation_state->value = 'published';
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    parent::setUnpublished();
    $this->moderation_state->value = 'draft';
  }

  /**
   * {@inheritdoc}
   */
  public function setDeleted() {
    parent::setUnpublished();
    $this->isDefaultRevision(TRUE);
    $this->setNewRevision(TRUE);
    $this->setRevisionTranslationAffectedEnforced(TRUE);
    $this->moderation_state->value = 'trash';
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    if ($this->isNew()) {
      return FALSE;
    }
    return $this->getLatestRevision()->moderation_state->value == 'trash';
  }

  /**
   * {@inheritdoc}
   */
  public function getVersionId() {
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_ids = $node_storage->revisionIds($this);
    $version_key = array_search($this->getRevisionId(), $revision_ids);
    return $version_key !== FALSE ? $version_key + 1 : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getModerationStateLabel() {
    /** @var \Drupal\content_moderation\ModerationInformation $moderation_information */
    $moderation_information = \Drupal::service('content_moderation.moderation_information');
    return $moderation_information->getOriginalState($this)->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentStatus() {
    if ($this->isDeleted()) {
      return self::CONTENT_STATUS_DELETED;
    }
    if ($this->getLatestRevision() && $this->getLatestRevision()->isPublished()) {
      return self::CONTENT_STATUS_PUBLISHED;
    }

    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_ids = $node_storage->revisionIds($this);
    $revisions = $node_storage->loadMultipleRevisions($revision_ids);

    $count_published = count(array_filter($revisions, function (NodeInterface $revision) {
      return $revision->isPublished();
    }));
    // If there are no published versions we call it "Draft". If at least one
    // published version exists we call it "Published with newer draft".
    return !$count_published ? self::CONTENT_STATUS_DRAFT : self::CONTENT_STATUS_PUBLISHED_WITH_DRAFT;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentStatusLabel() {
    $content_status = $this->getContentStatus();
    $label_map = [
      self::CONTENT_STATUS_PUBLISHED => $this->t('Published'),
      self::CONTENT_STATUS_PUBLISHED_WITH_DRAFT => $this->t('Published with newer draft'),
      self::CONTENT_STATUS_DRAFT => $this->t('Draft'),
      self::CONTENT_STATUS_DELETED => $this->t('Deleted'),
    ];
    return $label_map[$content_status];
  }

  /**
   * {@inheritdoc}
   */
  public function getVersionStatusLabel() {
    if ($this->getLatestRevision()->isDeleted()) {
      return $this->t('Deleted');
    }
    if ($this->isPublished()) {
      return $this->t('Published');
    }
    return $this->isLatestRevision() ? $this->t('Draft') : $this->t('Archived');
  }

  /**
   * Get the latest revision.
   *
   * @return \Drupal\ncms_ui\Entity\Content\ContentBase|null
   *   The latest revision if available.
   */
  public function getLatestRevision() {
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_id = $node_storage->getLatestRevisionId($this->id());
    return $revision_id ? $node_storage->loadRevision($revision_id) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastPublishedRevision() {
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_ids = array_reverse($node_storage->revisionIds($this));

    /** @var \Drupal\node\NodeInterface[] $revisions */
    $revisions = $node_storage->loadMultipleRevisions($revision_ids);
    foreach ($revisions as $revision) {
      if (!$revision->isPublished()) {
        continue;
      }
      return $revision;
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousRevision() {
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $revision_ids = array_reverse($node_storage->revisionIds($this));
    array_shift($revision_ids);
    $previous_revision_id = array_shift($revision_ids);

    /** @var \Drupal\node\NodeInterface[] $revisions */
    return $node_storage->loadRevision($previous_revision_id);
  }

  /**
   * Retrieve entity operations specific to our workflows.
   *
   * @return array
   *   An array of operation links.
   */
  public function getEntityOperations() {
    $operations = [];
    if (!$this->isDeleted() && $this->access('update')) {
      if ($this instanceof ContentVersionInterface) {
        $operations['versions'] = [
          'title' => $this->t('Versions'),
          'url' => Url::fromRoute('entity.node.version_history', [
            'node' => $this->id(),
          ]),
          'weight' => 50,
        ];
      }
      $operations['soft_delete'] = [
        'title' => $this->t('Move to trash'),
        'url' => Url::fromRoute('entity.node.soft_delete', [
          'node' => $this->id(),
        ], [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '80%',
              'title' => $this->t('Confirm deletion'),
              'dialogClass' => 'node-confirm',
            ]),
          ],
        ]),
        'weight' => 50,
      ];
    }
    if ($this->access('restore')) {
      $operations['restore'] = [
        'title' => $this->t('Restore'),
        'url' => Url::fromRoute('entity.node.restore', [
          'node' => $this->id(),
        ], [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '80%',
              'title' => $this->t('Confirm restore'),
              'dialogClass' => 'node-confirm',
            ]),
          ],
        ]),
        'weight' => 50,
      ];
    }
    if ($this->access('delete')) {
      $operations['delete'] = [
        'title' => $this->t('Delete for ever'),
        'url' => Url::fromRoute('entity.node.delete_form', [
          'node' => $this->id(),
        ], [
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '80%',
              'title' => $this->t('Confirm delete'),
              'dialogClass' => 'node-confirm',
            ]),
          ],
        ]),
        'weight' => 50,
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->isSyncing()) {
      $this->setChangedTime((new DrupalDateTime())->getTimestamp());
    }
    $this->setRevisionTranslationAffectedEnforced(TRUE);
  }

  /**
   * Get the route match service.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The route match service.
   */
  public static function getRouteMatch() {
    return \Drupal::routeMatch();
  }

}
