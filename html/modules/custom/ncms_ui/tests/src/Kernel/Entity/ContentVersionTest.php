<?php

namespace Drupal\Tests\ncms_ui\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Storage\ContentStorage;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the article entity.
 *
 * @group ncms_ui
 */
class ContentVersionTest extends KernelTestBase {

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
   * Test deletion of the last revision.
   *
   * @covers \Drupal\ncms_ui\Entity\Storage\ContentStorage::deleteLatestRevision()
   */
  public function testDeleteLatestRevision() {
    $article = Article::create([
      'title' => 'Article title #1',
    ]);
    $this->assertTrue($article->isNew());
    $this->assertFalse($article->isDeleted());
    $this->assertNull($article->getVersionId());
    $article->save();
    $this->assertFalse($article->isNew());
    $this->assertEquals(1, $article->getVersionId());

    $article->title = 'Article title #2';
    $article->save();
    $this->assertEquals(2, $article->getVersionId());
    $this->assertEquals('Article title #2', $article->label());
    $this->assertTrue($article->getLatestRevision()->isDefaultRevision());

    $this->assertNull($article->getLastPublishedRevision());

    $previous_revision = $article->getPreviousRevision();
    $this->assertInstanceof(Article::class, $previous_revision);
    $this->assertEquals(1, $previous_revision->getVersionId());

    $article->setDeleted();
    $article->save();
    $this->assertEquals(3, $article->getVersionId());
    $this->assertEquals('Article title #2', $article->label());

    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->assertInstanceOf(ContentStorage::class, $node_storage);
    $node_storage->deleteLatestRevision($article);

    $article = Article::load($article->id());
    $this->assertEquals(2, $article->getVersionId());
    $this->assertEquals('Article title #2', $article->label());

    // Create a new published version, to test that the deletion of the second
    // trashed revision further down does not accidentally set the default
    // revision to the published one.
    $article->title = 'Article title #3';
    $article->setPublished();
    $article->save();
    $this->assertEquals(3, $article->getVersionId());
    $this->assertEquals('Article title #3', $article->label());

    // Move the article to the trash.
    $article->setDeleted();
    $article->save();
    $this->assertEquals(4, $article->getVersionId());
    $this->assertEquals('Article title #3', $article->label());
    $this->assertFalse($article->isPublished());

    // Intentionally create a problem, be created a second deleted revision.
    // Our interface logic prevents that from happening, but it might happen
    // when updating nodes programmatically, so we want to make sure the
    // revision delete logic can cope with that situation.
    $article->setDeleted();
    $article->setNewRevision(TRUE);
    $article->save();
    $this->assertEquals(5, $article->getVersionId());
    $this->assertEquals('Article title #3', $article->label());
    $this->assertFalse($article->isPublished());

    // Now delete the last revision, which should revert the article back to
    // the previous deleted revision which should not be published.
    $node_storage->deleteLatestRevision($article);
    $article = Article::load($article->id());
    $this->assertEquals(4, $article->getVersionId());
    $this->assertEquals('Article title #3', $article->label());
    $this->assertFalse($article->isPublished());

    // And delete again, which should leave us with the published revision in
    // the end, having restored the article from the trash bin.
    $node_storage->deleteLatestRevision($article);
    $article = Article::load($article->id());
    $this->assertEquals(3, $article->getVersionId());
    $this->assertEquals('Article title #3', $article->label());
    $this->assertTrue($article->isPublished());
  }

}
