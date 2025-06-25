<?php

namespace Drupal\ncms_ui\Entity\Media;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\ncms_ui\Entity\EntityOverviewInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;

/**
 * Bundle base class for media entities.
 */
abstract class MediaBase extends Media implements ContentSpaceAwareInterface, EntityOverviewInterface {

  use ContentSpaceEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel == 'canonical' && !$this->access('update')) {
      // The canonical url for media entities is the edit url. In cases where
      // access to the edit form is forbidden, we need to use a different url
      // here, so we use the actual image url.
      $thumbnail_uri = $this->getThumbnailUri(FALSE);
      $path = self::filUrlGenerator()->generateAbsoluteString($thumbnail_uri);
      return Url::fromUri($path);
    }
    return parent::toUrl();
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
    if (!$this->isDeleted() && $this->access('update')) {
      $operations['soft_delete'] = [
        'title' => $this->t('Move to trash'),
        'url' => Url::fromRoute('entity.media.soft_delete', [
          'media' => $this->id(),
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
        'url' => Url::fromRoute('entity.media.restore', [
          'media' => $this->id(),
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
          'media' => $this->id(),
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
  public function isDeleted() {
    if ($this->isNew()) {
      return FALSE;
    }
    return $this->getLatestRevision()->isModerationState('trash');
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
