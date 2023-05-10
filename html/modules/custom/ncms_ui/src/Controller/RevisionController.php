<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Entity\ContentBase;
use Drupal\ncms_ui\Entity\ContentStorage;
use Drupal\node\NodeInterface;

/**
 * Implementation of the RevisionController class.
 */
class RevisionController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Publish a node revision.
   *
   * @param \Drupal\node\NodeInterface $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function publish(NodeInterface $node_revision) {
    if ($this->setRevisionStatus($node_revision, NodeInterface::PUBLISHED)) {
      $this->messenger()->addStatus($this->t('The revision has been published'));
    }
    return $this->redirect('entity.node.version_history', ['node' => $node_revision->id()]);
  }

  /**
   * Publish a node revision.
   *
   * @param \Drupal\node\NodeInterface $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function unpublish(ContentBase $node_revision) {
    if ($this->setRevisionStatus($node_revision, NodeInterface::NOT_PUBLISHED)) {
      $this->messenger()->addStatus($this->t('The revision has been unpublished'));

      // Also see if there is the default revision, if so, we need to find a
      // new default revision.
      // @todo Implement
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
