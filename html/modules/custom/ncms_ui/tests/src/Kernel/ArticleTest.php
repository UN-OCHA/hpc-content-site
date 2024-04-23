<?php

namespace Drupal\Tests\ncms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests the article entity.
 *
 * @group ncms_ui
 */
class ArticleTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UserCreationTrait;
  use ContentTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'node',
    'taxonomy',
    'text',
    'views',
    'replicate',
    'replicate_ui',
    'workflows',
    'content_moderation',
    'ncms_publisher',
    'ncms_ui',
    'ncms_ui_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('content_moderation_state');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('workflow');
    $this->installConfig(['replicate_ui']);
    $this->installConfig([
      'field',
      'system',
      'node',
      'taxonomy',
      'content_moderation',
      'ncms_ui',
    ]);

    $this->createContentType([
      'type' => 'article',
    ]);
    $workflow = $this->createArticleWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'article');
    $workflow->save();
  }

  /**
   * Test the overview url.
   */
  public function testGetOverviewUrl() {
    $article = Article::create([
      'title' => 'Article title',
    ]);
    $this->assertEquals('/admin/content', $article->getOverviewUrl()->toString());
  }

  /**
   * Test the access method.
   */
  public function testAccess() {
    $this->createVocabulary([
      'vid' => 'content_space',
    ]);
    $this->setupContentSpaceStructure();

    $content_space = $this->createContentSpace();
    $user = $this->setUpCurrentUser([
      'field_content_spaces' => [['target_id' => $content_space->id()]],
    ], [], TRUE);
    /** @var \Drupal\ncms_ui\Entity\Content\Article $article */
    $article = $this->createArticleInContentSpace('Article in content space', $content_space->id());
    $this->assertInstanceOf(Article::class, $article);
    $this->assertEquals($content_space->id(), $article->getContentSpace()->id());
    $this->assertEquals(TRUE, $article->hasContentSpaceAccess($user));
    $this->assertTrue($article->access('update'));
    $this->assertTrue($article->access('publish revision'));
    $this->assertFalse($article->access('restore'));

    $article->setDeleted();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertTrue($article->access('restore'));

    $user = $this->setUpCurrentUser([], [], TRUE);
    $this->assertFalse($article->access('delete'));
  }

  /**
   * Test the moderation states.
   */
  public function testModerationStates() {
    $article = Article::create([
      'title' => 'Article title',
    ]);
    $this->assertTrue($article->isNew());
    $this->assertFalse($article->isDeleted());
    $this->assertNull($article->getVersionId());
    $article->save();
    $this->assertFalse($article->isNew());
    $this->assertEquals(1, $article->getVersionId());

    $article->setUnpublished();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('draft', $article->moderation_state->value);
    $this->assertEquals('Draft', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_DRAFT, $article->getContentStatus());
    $this->assertEquals('Draft', $article->getContentStatusLabel());
    $this->assertEquals('Draft', $article->getVersionStatusLabel());

    $article->setPublished();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('published', $article->moderation_state->value);
    $this->assertEquals('Published', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_PUBLISHED, $article->getContentStatus());
    $this->assertEquals('Published', $article->getContentStatusLabel());
    $this->assertEquals('Published', $article->getVersionStatusLabel());

    $article->setUnpublished();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('draft', $article->moderation_state->value);
    $this->assertEquals('Draft', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_PUBLISHED_WITH_DRAFT, $article->getContentStatus());
    $this->assertEquals('Published with newer draft', $article->getContentStatusLabel());
    $this->assertEquals('Draft', $article->getVersionStatusLabel());

    $article->setDeleted();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('trash', $article->moderation_state->value);
    $this->assertEquals('Archived', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_DELETED, $article->getContentStatus());
    $this->assertEquals('Deleted', $article->getContentStatusLabel());
    $this->assertEquals('Deleted', $article->getVersionStatusLabel());

    $revision = $article->getPreviousRevision();
    $this->assertInstanceOf(ContentVersionInterface::class, $revision);
    $this->assertEquals('draft', $revision->moderation_state->value);

    $this->assertEquals(TRUE, $article->isDeleted());
  }

  /**
   * Test the entity operations.
   */
  public function testEntityOperations() {
    $this->createVocabulary([
      'vid' => 'content_space',
    ]);
    $this->setupContentSpaceStructure();

    $content_space = $this->createContentSpace();
    $user = $this->setUpCurrentUser([
      'field_content_spaces' => [['target_id' => $content_space->id()]],
    ], [], TRUE);
    /** @var \Drupal\ncms_ui\Entity\Content\Article $article */
    $article = $this->createArticleInContentSpace('Article in content space', $content_space->id());
    $this->assertInstanceOf(Article::class, $article);
    $this->assertEquals(TRUE, $article->hasContentSpaceAccess($user));

    $operations = $article->getEntityOperations();
    $this->assertIsArray($operations);
    $this->assertArrayHasKey('versions', $operations);
    $this->assertArrayHasKey('soft_delete', $operations);
    $this->assertArrayNotHasKey('restore', $operations);
    $this->assertArrayNotHasKey('delete', $operations);

    $article->setDeleted();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals(TRUE, $article->isDeleted());

    $operations = $article->getEntityOperations();
    $this->assertIsArray($operations);
    $this->assertArrayNotHasKey('versions', $operations);
    $this->assertArrayNotHasKey('soft_delete', $operations);
    $this->assertArrayHasKey('restore', $operations);
    $this->assertArrayHasKey('delete', $operations);
  }

  /**
   * Creates the article workflow.
   *
   * @return \Drupal\workflows\Entity\Workflow
   *   The editorial workflow entity.
   */
  protected function createArticleWorkflow() {
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
