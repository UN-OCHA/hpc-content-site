<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\ContentRevisionWorkflow;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the RevisionController class.
 */
class RevisionController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The content revision workflow service.
   *
   * @var \Drupal\ncms_ui\ContentRevisionWorkflow
   */
  private ContentRevisionWorkflow $contentRevisionWorkflow;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->contentRevisionWorkflow = $container->get('ncms_ui.content_revision_workflow');
    return $instance;
  }

  /**
   * Publish a node revision.
   *
   * @param \Drupal\ncms_ui\Entity\ContentInterface $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function publish(ContentInterface $node_revision) {
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
   * @param \Drupal\ncms_ui\Entity\ContentInterface $node_revision
   *   The node revision.
   *
   * @return array
   *   An array suitable for \Drupal\Core\Render\RendererInterface::render().
   */
  public function unpublish(ContentInterface $node_revision) {
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
   * @param \Drupal\ncms_ui\Entity\ContentInterface $node_revision
   *   The node revision.
   * @param int $status
   *   The status of the revision.
   *
   * @return bool
   *   TRUE if the operation was successful, FALSE otherwise.
   */
  private function setRevisionStatus(ContentInterface $node_revision, $status) {
    $status_values = [
      NodeInterface::PUBLISHED,
      NodeInterface::NOT_PUBLISHED,
    ];
    if (!in_array($status, $status_values)) {
      throw new \InvalidArgumentException("Invalid status for node revisions: {$status}");
    }
    return $status == NodeInterface::PUBLISHED
      ? $this->contentRevisionWorkflow->publishExistingRevision($node_revision)
      : $this->contentRevisionWorkflow->unpublishExistingRevision($node_revision);
  }

}
