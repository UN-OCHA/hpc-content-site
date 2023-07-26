<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\Storage\ContentStorage;
use Drupal\node\NodeInterface;

/**
 * Implementation of the RevisionController class.
 */
class RevisionController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Publish a node revision.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentBase $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function publish(ContentBase $node_revision) {
    if ($this->setRevisionStatus($node_revision, NodeInterface::PUBLISHED)) {
      $this->messenger()->addStatus($this->t('Version #@version has been published', [
        '@version' => $node_revision->getVersionId(),
      ]));
      $last_version = $node_revision->getLastPublishedRevision();
      if ($last_version && $last_version->getVersionId() <= $node_revision->getVersionId()) {
        $this->messenger()->addStatus($this->t('New default published version is #@version', [
          '@version' => $last_version->getVersionId(),
        ]));
      }
    }
    return $this->redirect('entity.node.version_history', ['node' => $node_revision->id()]);
  }

  /**
   * Publish a node revision.
   *
   * @param \Drupal\ncms_ui\Entity\Content\ContentBase $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function unpublish(ContentBase $node_revision) {
    if ($this->setRevisionStatus($node_revision, NodeInterface::NOT_PUBLISHED)) {
      $this->messenger()->addStatus($this->t('Version #@version has been unpublished', [
        '@version' => $node_revision->getVersionId(),
      ]));
      if ($last_version = $node_revision->getLastPublishedRevision()) {
        if ($last_version->getVersionId() <= $node_revision->getVersionId()) {
          $this->messenger()->addStatus($this->t('New default published version is #@version', [
            '@version' => $last_version->getVersionId(),
          ]));
        }
      }
    }
    return $this->redirect('entity.node.version_history', ['node' => $node_revision->id()]);
  }

  /**
   * Publish a node revision.
   *
   * @param \Drupal\node\NodeInterface $node_revision
   *   The node revision.
   * @param int $status
   *   The status of the revision.
   *
   * @return bool
   *   TRUE if the operation was successfull, FALSE otherwise.
   */
  private function setRevisionStatus(NodeInterface $node_revision, $status) {
    $status_values = [
      NodeInterface::PUBLISHED,
      NodeInterface::NOT_PUBLISHED,
    ];
    if (!in_array($status, $status_values)) {
      throw new \InvalidArgumentException("Invalid status for node revisions: {$status}");
    }
    $content_storage = $this->entityTypeManager()->getStorage('node');
    if (!$content_storage instanceof ContentStorage) {
      return FALSE;
    }
    return (bool) $content_storage->updateRevisionStatus($node_revision, $status);
  }

}
