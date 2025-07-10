<?php

namespace Drupal\Tests\ncms_ui\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

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
    'image',
    'node',
    'media',
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
    $this->installEntitySchema('media');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('workflow');
    $this->installConfig(['replicate_ui']);
    $this->installConfig([
      'field',
      'system',
      'image',
      'node',
      'media',
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
   * Test the bundle label.
   */
  public function testGetBundleLabel() {
    $article = Article::create();
    $this->assertEquals('article', $article->getBundleLabel());
  }

  /**
   * Test the access method.
   */
  public function testAccess() {
    $this->createVocabulary([
      'vid' => 'content_space',
    ]);
    $this->setupContentSpaceStructure();
    $this->addContentSpaceFieldToBundle('article');

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
    $this->assertEquals('draft', $article->getModerationState());
    $this->assertEquals('Draft', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_DRAFT, $article->getContentStatus());
    $this->assertEquals('Draft', $article->getContentStatusLabel());
    $this->assertEquals('Draft', $article->getVersionStatusLabel());

    $article->setPublished();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('published', $article->getModerationState());
    $this->assertEquals('Published', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_PUBLISHED, $article->getContentStatus());
    $this->assertEquals('Published', $article->getContentStatusLabel());
    $this->assertEquals('Published', $article->getVersionStatusLabel());

    $article->setUnpublished();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('draft', $article->getModerationState());
    $this->assertEquals('Draft', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_PUBLISHED_WITH_DRAFT, $article->getContentStatus());
    $this->assertEquals('Published with newer draft', $article->getContentStatusLabel());
    $this->assertEquals('Draft', $article->getVersionStatusLabel());

    $article->setDeleted();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals('trash', $article->getModerationState());
    $this->assertEquals('Archived', $article->getModerationStateLabel());
    $this->assertEquals(ContentBase::CONTENT_STATUS_DELETED, $article->getContentStatus());
    $this->assertEquals('Deleted', $article->getContentStatusLabel());
    $this->assertEquals('Deleted', $article->getVersionStatusLabel());

    $revision = $article->getPreviousRevision();
    $this->assertInstanceOf(ContentVersionInterface::class, $revision);
    $this->assertEquals('draft', $revision->getModerationState());

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
    $this->addContentSpaceFieldToBundle('article');

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

}
