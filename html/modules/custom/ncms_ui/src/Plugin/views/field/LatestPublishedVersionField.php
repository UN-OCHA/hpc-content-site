<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that shows the latest version for a node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("latest_published_version_field")
 */
class LatestPublishedVersionField extends FieldPluginBase {

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
    foreach ($rows as &$row) {
      $row->latest_published_version = $row->_entity->getLastPublishedRevision();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $revision */
    $revision = $row->latest_published_version;
    if (!$revision) {
      return NULL;
    }
    $build = [
      '#type' => 'link',
      '#url' => new Url('entity.node.revision', [
        'node' => $revision->id(),
        'node_revision' => $revision->getRevisionId(),
      ]),
      '#title' => new FormattableMarkup('#@version (@moderation_status)', [
        '@version' => $revision instanceof ContentVersionInterface ? $revision->getVersionId() : $revision->getRevisionId(),
        '@moderation_status' => $revision instanceof ContentVersionInterface ? $revision->getVersionStatus() : ($revision->isPublished() ? $this->t('Published') : $this->t('Unpublished')),
      ]),
    ];
    return $build;
  }

}
