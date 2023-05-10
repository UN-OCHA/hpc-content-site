<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that shows the latest version for a node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("latest_version_field")
 */
class LatestVersionField extends FieldPluginBase {

  /**
   * The entity typemanager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Overridden to prevent any additional query.
  }

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
    $build = new FormattableMarkup('#@version (@moderation_status)', [
      '@version' => $revision instanceof ContentVersionInterface ? $revision->getVersionId() : $revision->getRevisionId(),
      '@moderation_status' => $revision instanceof ContentVersionInterface ? $revision->getModerationStateLabel() : ($revision->isPublished() ? $this->t('Published') : $this->t('Unpublished')),
    ]);
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
