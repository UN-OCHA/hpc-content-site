<?php

namespace Drupal\Tests\ncms_ui\Traits;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\ncms_ui\Entity\Taxonomy\ContentSpace;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Common helper trait for functional and webdriver tests.
 */
trait ContentTestTrait {

  use EntityReferenceFieldCreationTrait;
  use ContentTypeCreationTrait;
  use ParagraphsTestBaseTrait;
  use TaxonomyTestTrait;

  /**
   * Setup the content structure for documents.
   */
  protected function setupDocumentStructure(): void {
    // Create the necessary content structure for documents.
    $this->addParagraphedContentType('document');

    $this->addParagraphsType('document_chapter');
    $settings = [
      'target_type' => 'node',
    ];
    $this->addFieldtoParagraphType('document_chapter', 'field_articles', 'entity_reference', $settings);
    $storage = FieldStorageConfig::loadByName('paragraph', 'field_articles');
    $storage->setCardinality(FieldStorageConfig::CARDINALITY_UNLIMITED);
    $storage->save();

    $settings = [
      'open' => TRUE,
      'entity_browser' => 'articles',
      'field_widget_display' => 'label',
      'field_widget_edit' => '1',
      'field_widget_remove' => '1',
      'selection_mode' => 'selection_append',
      'additional_fields' => [
        'options' => [
          'status' => 'status',
        ],
      ],
      'field_widget_replace' => 0,
      'field_widget_display_settings' => [],
    ];
    $this->setParagraphsWidgetSettings('document_chapter', 'field_articles', $settings, 'entity_browser_entity_reference', 'paragraph');

    $handler_settings = [
      'target_bundles' => [
        'article' => 'article',
      ],
    ];
    $this->createEntityReferenceField('node', 'document', 'field_articles', 'Articles', 'node', 'default', $handler_settings);

    $handler_settings = [
      'target_bundles' => [
        'document' => 'document',
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_documents', 'Documents', 'node', 'default', $handler_settings);
  }

  /**
   * Setup the content types.
   */
  protected function setupContentSpaceStructure() {
    $handler_settings = [
      'target_bundles' => [
        'tags' => 'major_tags',
      ],
    ];
    $this->createEntityReferenceField('taxonomy_term', 'content_space', 'field_major_tags', 'Tags', 'taxonomy_term', 'default', $handler_settings);

    $this->createEntityReferenceField('user', 'user', 'field_content_spaces', 'Content spaces', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Make content be replicable.
    $this->config('replicate_ui.settings')
      ->set('entity_types', ['node'])
      ->save();
    \Drupal::service('router.builder')->rebuild();
    Cache::invalidateTags(['entity_types']);

    node_access_rebuild(TRUE);
  }

  /**
   * Add a content space reference field to the given node bundle.
   *
   * @param string $bundle
   *   The node bundle to which the field should be added.
   */
  protected function addContentSpaceFieldToBundle($bundle): void {
    $handler_settings = [
      'target_bundles' => [
        'content_space' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('node', $bundle, 'field_content_space', 'Content space', 'taxonomy_term', 'default', $handler_settings);
    FieldConfig::loadByName('node', $bundle, 'field_content_space')
      ->set('required', TRUE)
      ->save();
    EntityFormDisplay::load('node.' . $bundle . '.default')
      ->setComponent('field_content_space', [
        'type' => 'options_select',
        'region' => 'content',
      ])
      ->save();

    $handler_settings = [
      'target_bundles' => [
        'content_space' => 'content_space',
      ],
    ];
  }

  /**
   * Create a major tag term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term object.
   */
  protected function createMajorTag(): TermInterface {
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'major_tags',
    ]);
    $term->save();
    return $term;
  }

  /**
   * Create a content space term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term object.
   */
  protected function createContentSpace(): TermInterface {
    $tag = $this->createMajorTag();
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'content_space',
      'field_major_tags' => ['target_id' => $tag->id()],
    ]);
    $term->save();
    return $term;
  }

  /**
   * Create a document in the given content space.
   *
   * @param string $title
   *   The title of the node.
   * @param int $content_space_id
   *   The id of the content space.
   * @param int $status
   *   The published status of the document.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node object.
   */
  protected function createDocumentInContentSpace($title, $content_space_id, $status = NodeInterface::PUBLISHED): NodeInterface {
    $node = Node::create([
      'type' => 'document',
      'title' => $title,
      'field_content_space' => ['target_id' => $content_space_id],
      'status' => $status,
      'moderation_state' => [
        'value' => $status == NodeInterface::PUBLISHED ? 'published' : 'draft',
      ],
    ]);
    $result = $node->save();
    $this->assertEquals($result, SAVED_NEW);
    return $node;
  }

  /**
   * Create an article in the given content space.
   *
   * @param string $title
   *   The title of the node.
   * @param int $content_space_id
   *   The id of the content space.
   * @param int $status
   *   The published status of the article.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node object.
   */
  protected function createArticleInContentSpace($title, $content_space_id, $status = NodeInterface::PUBLISHED): NodeInterface {
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'field_content_space' => ['target_id' => $content_space_id],
      'status' => $status,
      'moderation_state' => [
        'value' => $status == NodeInterface::PUBLISHED ? 'published' : 'draft',
      ],
    ]);
    $result = $node->save();
    $this->assertEquals($result, SAVED_NEW);
    return $node;
  }

  /**
   * Create a chapter paragraph.
   *
   * @param \Drupal\ncms_ui\Entity\Content\Articles[] $articles
   *   An array of articles to add to the chapter.
   * @param \Drupal\node\NodeInterface $parent_entity
   *   The entity to attach the chapter paragraph to.
   * @param string $parent_field_name
   *   The name of the field.
   */
  protected function createChapter($articles = [], $parent_entity = NULL, $parent_field_name = 'field_paragraphs'): void {
    $chapter = Paragraph::create([
      'type' => 'document_chapter',
      'field_articles' => $articles,
    ]);
    $chapter->save();
    if ($parent_entity !== NULL) {
      // Add the chapter to the document.
      $parent_entity->get('field_paragraphs')->setValue([$chapter]);
      $parent_entity->save();
      $chapter->setParentEntity($parent_entity, $parent_field_name);
    }
    $chapter->save();
  }

  /**
   * Create a user with permissions and associated content spaces.
   *
   * @param array $content_spaces
   *   An array of content space term objects.
   * @param array $permissions
   *   Additional permissions to give to the user.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user object or FALSE.
   */
  protected function createEditorUserWithContentSpaces(array $content_spaces, $permissions = []) {
    return $this->drupalCreateUser(array_merge([
      'access content overview',
      'access administration pages',
      'view the administration theme',
      'create article content',
      'edit own article content',
      'view article revisions',
      'revert article revisions',
      'replicate entities',
      'use article_workflow transition create_new_draft',
      'use article_workflow transition delete',
      'use article_workflow transition publish',
      'use article_workflow transition restore_draft',
      'use article_workflow transition restore_publish',
      'use article_workflow transition save_draft_leave_current_published',
      'use article_workflow transition update',
    ], $permissions), NULL, NULL, [
      'field_content_spaces' => array_map(function ($content_space) {
        return ['target_id' => $content_space->id()];
      }, $content_spaces),
    ]);
  }

  /**
   * Set the content space for the current user session.
   *
   * @param \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace $content_space
   *   The content space to activate.
   */
  protected function setContentSpace($content_space): void {
    /** @var \Drupal\ncms_ui\ContentSpaceManager $content_manager */
    $content_manager = $this->container->get('ncms_ui.content_space.manager');
    $content_manager->setCurrentContentSpaceId($content_space->id());

    /** @var \Drupal\Core\Cache\CacheBackendInterface $render_cache */
    $render_cache = $this->container->get('cache.render');
    $render_cache->invalidateAll();
  }

  /**
   * Get the currently active content space for the current user session.
   *
   * @return \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace
   *   The content space to activate.
   */
  protected function getContentSpace(): ContentSpace {
    /** @var \Drupal\ncms_ui\ContentSpaceManager $content_manager */
    $content_manager = $this->container->get('ncms_ui.content_space.manager');
    return $content_manager->getCurrentContentSpace();
  }

  /**
   * Creates the article workflow.
   *
   * @return \Drupal\workflows\Entity\Workflow
   *   The editorial workflow entity.
   */
  protected function createArticleWorkflow(): Workflow {
    $workflow = Workflow::create([
      'type' => 'content_moderation',
      'id' => 'article_workflow',
      'label' => 'Publishing (with draft and soft delete)',
      'type_settings' => [
        'states' => [
          'trash' => [
            'label' => 'Archived',
            'weight' => 5,
            'published' => FALSE,
            'default_revision' => TRUE,
          ],
          'draft' => [
            'label' => 'Draft',
            'published' => FALSE,
            'default_revision' => FALSE,
            'weight' => -2,
          ],
          'published' => [
            'label' => 'Published',
            'published' => TRUE,
            'default_revision' => TRUE,
            'weight' => 0,
          ],
        ],
        'transitions' => [
          'create_new_draft' => [
            'label' => 'Create New Draft',
            'to' => 'draft',
            'weight' => 0,
            'from' => [
              'draft',
            ],
          ],
          'delete' => [
            'label' => 'Archive',
            'from' => ['draft', 'published'],
            'to' => 'trash',
            'weight' => 2,
          ],
          'publish' => [
            'label' => 'Publish',
            'to' => 'published',
            'weight' => 1,
            'from' => [
              'draft',
            ],
          ],
          'restore_draft' => [
            'label' => 'Restore to draft',
            'to' => 'draft',
            'weight' => 1,
            'from' => [
              'trash',
            ],
          ],
          'restore_publish' => [
            'label' => 'Restore and Publish',
            'to' => 'published',
            'weight' => 1,
            'from' => [
              'trash',
            ],
          ],

          'save_draft_leave_current_published' => [
            'label' => 'Create draft (leave current version published)',
            'from' => ['published'],
            'to' => 'draft',
            'weight' => 3,
          ],
          'update' => [
            'label' => 'Update',
            'from' => ['published'],
            'to' => 'published',
            'weight' => 4,
          ],
        ],
      ],
    ]);
    $workflow->save();
    return $workflow;
  }

}
