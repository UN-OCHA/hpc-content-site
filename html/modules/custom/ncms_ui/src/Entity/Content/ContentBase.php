<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Bundle class for organization nodes.
 */
class ContentBase extends Node implements ContentSpaceAwareInterface, ContentVersionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    // These operations are used in ncms_ui.routing.yml and should be mapped to
    // the 'update' operation.
    $status_operations = [
      'publish revision',
      'unpublish revision',
    ];
    if (in_array($operation, $status_operations)) {
      $operation = 'update';
    }
    // This override exists to set the operation to the default value "view".
    return parent::access($operation, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentSpace() {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    return $this->get('field_content_space')->entity ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentSpace($content_space_id) {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    $this->get('field_content_space')->setValue(['target_id' => $content_space_id]);
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
    if ($this->getLatestRevision() && $this->getLatestRevision()->isPublished()) {
      return $this->t('Published');
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
    return !$count_published ? $this->t('Draft') : $this->t('Published with newer draft');
  }

  /**
   * Get the status of the revision.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status.
   */
  public function getVersionStatus() {
    if ($this->isPublished()) {
      return $this->t('Published');
    }
    return $this->isLatestRevision() ? $this->t('Draft') : $this->t('Archived');
  }

  /**
   * Get the latest revision.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The latest revision if available.
   */
  protected function getLatestRevision() {
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

}
