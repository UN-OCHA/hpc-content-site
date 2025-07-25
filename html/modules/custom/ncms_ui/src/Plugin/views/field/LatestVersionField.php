<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Plugin\views\ContentBaseField;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;

/**
 * Provides a field that shows the latest version for a node.
 */
#[ViewsField("latest_version_field")]
class LatestVersionField extends ContentBaseField {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$rows) {
    $node_ids = array_map(function (ResultRow $row) {
      return $row->_entity->id();
    }, $rows);
    $revisions = $this->loadLastRevisionsForNodeIds($node_ids);
    foreach ($rows as &$row) {
      $row->latest_version = $revisions[$row->_entity->id()];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $revision */
    $revision = $row->latest_version;
    $build = [
      '#type' => 'link',
      '#url' => new Url('entity.node.revision', [
        'node' => $revision->id(),
        'node_revision' => $revision->getRevisionId(),
      ]),
      '#title' => [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => new FormattableMarkup('#@version (@moderation_status)', [
          '@version' => $revision instanceof ContentVersionInterface ? $revision->getVersionId() : $revision->getRevisionId(),
          '@moderation_status' => $revision instanceof ContentVersionInterface ? $revision->getVersionStatusLabel() : ($revision->isPublished() ? $this->t('Published') : $this->t('Unpublished')),
        ]),
        '#attributes' => [
          'class' => array_filter([
            'marker',
            $revision->isPublished() ? 'marker--published' : NULL,
          ]),
        ],
      ],

    ];
    return $build;
  }

  /**
   * Load the latest revision for each of the given node ids.
   *
   * @param int[] $node_ids
   *   The node ids.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of node revisions, keyed by article node id.
   */
  private function loadLastRevisionsForNodeIds(array $node_ids) {
    if (empty($node_ids)) {
      return [];
    }
    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    $nodes = $node_storage->loadMultiple($node_ids);

    /** @var \Drupal\paragraphs\Entity\Paragraph[] $article_paragraphs */
    $revisions = array_map(function ($node) use ($node_storage) {
      $revision_ids = $node_storage->revisionIds($node);
      return $node_storage->loadRevision(end($revision_ids));
    }, $nodes);
    return $revisions;
  }

}
