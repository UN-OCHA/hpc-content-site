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
#[ViewsField("latest_published_version_field")]
class LatestPublishedVersionField extends ContentBaseField {

  /**
   * {@inheritdoc}
   */
  public function preRender(&$rows) {
    foreach ($rows as &$row) {
      if ($row->_entity instanceof ContentVersionInterface) {
        $row->latest_published_version = $row->_entity->getLastPublishedRevision();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $revision */
    $revision = $row->latest_published_version ?? NULL;
    if (!$revision) {
      return NULL;
    }
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

}
