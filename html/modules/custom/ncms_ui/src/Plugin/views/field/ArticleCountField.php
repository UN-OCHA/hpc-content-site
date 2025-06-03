<?php

namespace Drupal\ncms_ui\Plugin\views\field;

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\Content\Document;
use Drupal\ncms_ui\Plugin\views\ContentBaseField;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field that shows the count of articles for a document.
 */
#[ViewsField("article_count_field")]
class ArticleCountField extends ContentBaseField {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $entity_table_info = $this->query->getEntityTableInfo();
    $node_data_table_alias = $entity_table_info[$this->table]['alias'] ?? NULL;
    $fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $field_articles = $fields[Document::ARTICLES_FIELD] ?? NULL;
    if (!$node_data_table_alias || !$field_articles) {
      return;
    }

    /** @var \Drupal\node\NodeStorageInterface $node_storage*/
    $node_storage = $this->entityTypeManager->getStorage('node');
    if (!$node_storage instanceof SqlEntityStorageInterface) {
      return;
    }
    assert($node_storage instanceof SqlEntityStorageInterface);
    $tables = $node_storage->getTableMapping()->getAllFieldTableNames(Document::ARTICLES_FIELD);
    $field_articles_table = $tables[0];
    $target_column = array_key_first($field_articles->getColumns());
    $this->field_alias = $this->query->addField(NULL, "(SELECT COUNT({$field_articles->getName()}_{$target_column}) FROM {$field_articles_table} fa WHERE fa.entity_id = {$node_data_table_alias}.nid)", 'article_count');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['link_to_article_list'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['link_to_article_list'] = [
      '#title' => $this->t('Link to article list'),
      '#description' => $this->t('Make the number a link to the article list, filtered by the document.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['link_to_article_list']),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $count = $row->{$this->field_alias} ?? NULL;
    if (!$row->_entity instanceof Document || $count === NULL) {
      return NULL;
    }
    if (!empty($this->options['link_to_article_list'])) {
      $url = Url::fromRoute('view.content.page_articles', [], [
        'query' => [
          'document' => $row->_entity->id(),
        ],
      ]);
      $build = [
        '#type' => 'link',
        '#title' => $count,
        '#url' => $url,
      ];
    }
    else {
      $build = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $count,
      ];
    }
    return $build;
  }

}
