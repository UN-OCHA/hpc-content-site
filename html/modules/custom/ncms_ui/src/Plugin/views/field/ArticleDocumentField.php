<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that shows the document an article appears in.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("article_document_field")
 */
class ArticleDocumentField extends FieldPluginBase {

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
    $documents = $this->loadDocumentsForArticles($node_ids);
    foreach ($rows as &$row) {
      $row->article_document = $documents[$row->_entity->id()] ?? NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $items = [];
    foreach ($row->article_document ?? [] as $document_node) {
      /** @var \Drupal\node\NodeInterface $document_node */
      $items[] = $document_node->toLink()->toRenderable();
    }
    $build = [
      '#type' => 'inline_template',
      '#template' => '{{ items | safe_join(separator) }}',
      '#context' => ['separator' => ', ', 'items' => $items],
    ];
    return $build;
  }

  /**
   * Load document nodes for the given node ids.
   *
   * @param int[] $node_ids
   *   The node ids.
   *
   * @return array
   *   An array of arrays of document entities, keyed by article node id.
   */
  private function loadDocumentsForArticles(array $node_ids) {
    if (empty($node_ids)) {
      return [];
    }
    /** @var \Drupal\paragraphs\Entity\Paragraph[] $article_paragraphs */
    $article_paragraphs = $this->entityTypeManager->getStorage('paragraph')->loadByProperties([
      'type' => ['article', 'document_chapter'],
      'field_articles' => $node_ids,
    ]);
    $documents_by_article = [];
    foreach ($article_paragraphs as $paragraph) {
      $document = $paragraph->getParentEntity();
      if (!$document) {
        continue;
      }
      $article_nids = array_map(function ($item) {
        return $item['target_id'];
      }, $paragraph->get('field_articles')->getValue());
      foreach ($article_nids as $article_nid) {
        $documents_by_article[$article_nid] = $documents_by_article[$article_nid] ?? [];
        $documents_by_article[$article_nid][$document->id()] = $document;
      }
    }
    return $documents_by_article;
  }

}
