<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;
use Drupal\ncms_ui\Traits\EntityBundleLabelTrait;
use Drupal\ncms_ui\Traits\IframeDisplayContentTrait;
use Drupal\ncms_ui\Traits\ModalLinkTrait;
use Drupal\ncms_ui\Traits\ModeratedEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Bundle class for organization nodes.
 */
abstract class ContentBase extends Node implements ContentInterface {

  use StringTranslationTrait;
  use ContentSpaceEntityTrait;
  use IframeDisplayContentTrait;
  use EntityBundleLabelTrait;
  use ModeratedEntityTrait;
  use ModalLinkTrait;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation == 'view' && (!$this->isDeleted() || $this->hasContentSpaceAccess($account))) {
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
  public function toUrl($rel = 'canonical', array $options = []) {
    // Links pointing to the current revision point to the actual entity. So
    // instead of using the 'revision' link, use the 'canonical' link.
    if ($rel === 'revision' && $this->isDefaultRevision()) {
      $rel = 'canonical';
    }
    if ($rel == 'canonical') {
      return Url::fromRoute('entity.node.iframe', [
        'node' => $this->id(),
      ], $options);
    }
    if ($rel == 'revision') {
      return Url::fromRoute('entity.node_revision.iframe', [
        'node' => $this->id(),
        'node_revision' => $this->getRevisionId(),
      ], $options);
    }
    if ($rel == 'soft-delete-form' && $this->access('soft delete')) {
      return Url::fromRoute('entity.node.soft_delete', [
        'node' => $this->id(),
      ], $options);
    }
    if ($rel == 'restore-form' && $this->access('restore')) {
      return Url::fromRoute('entity.node.restore', [
        'node' => $this->id(),
      ], $options);
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getOverviewUrl();

  /**
   * {@inheritdoc}
   */
  public function hasTags() {
    $common_taxonomies = $this->getCommonTaxonomiesService();
    $supported_fields = $common_taxonomies->getCommonTaxonomyFieldNames();
    foreach ($supported_fields as $field_name) {
      if (!$this->hasField($field_name)) {
        continue;
      }
      if (!$this->get($field_name)->isEmpty()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    $terms = $this->getTagEntities();
    return array_map(function ($term) {
      return $term->getName();
    }, $terms);
  }

  /**
   * {@inheritdoc}
   */
  public function getTagEntities() {
    $common_taxonomies = $this->getCommonTaxonomiesService();
    $supported_fields = $common_taxonomies->getCommonTaxonomyFieldNames();
    $terms = [];
    // Iterating over $supported_fields to assure tags are ordered by
    // vocabulary first. This is not necessarily important, but makes things
    // look more consistent in the backend.
    foreach ($supported_fields as $field_name) {
      if (!$this->hasField($field_name)) {
        continue;
      }
      foreach (($this->get($field_name)?->referencedEntities() ?? []) as $term) {
        if (!$term instanceof Term) {
          continue;
        }
        $terms[$term->id()] = $term;
      }
    }
    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished() {
    parent::setPublished();
    $this->setModerationState('published');
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    parent::setUnpublished();
    $this->setModerationState('draft');
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

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    if ($this->isNew()) {
      return FALSE;
    }
    return $this->getLatestRevision()->isModerationState('trash');
  }

  /**
   * {@inheritdoc}
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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->isSyncing()) {
      $this->setChangedTime((new DrupalDateTime())->getTimestamp());
    }
    $this->setRevisionTranslationAffectedEnforced(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildMetaDataForDiff() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['metadata-wrapper'],
      ],
    ];
    $build['header'] = [
      '#markup' => new FormattableMarkup('<strong>@label</strong>', [
        '@label' => $this->t('Meta data'),
      ]),
    ];

    $meta_fields = [
      'status',
      'field_short_title',
      'field_computed_tags',
      'field_summary',
      'field_author',
      'field_pdf',
    ];
    foreach ($meta_fields as $field_name) {
      if (!$this->hasField($field_name)) {
        continue;
      }
      $build[$field_name] = $this->get($field_name)->view([
        'label' => 'inline',
      ]);
      if ($field_name == 'field_computed_tags' && !empty($build[$field_name][0]['#markup'])) {
        $build[$field_name]['#title'] = $this->t('Tags');
        $build[$field_name][0]['#markup'] = implode(', ', explode(',', $build[$field_name][0]['#markup']));
      }
    }
    return $build;
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

  /**
   * Get the common taxonomies service.
   *
   * @return \Drupal\ncms_tags\CommonTaxonomyService
   *   The common taxonomies service.
   */
  public function getCommonTaxonomiesService() {
    return \Drupal::service('ncms_tags.common_taxonomies');
  }

}
