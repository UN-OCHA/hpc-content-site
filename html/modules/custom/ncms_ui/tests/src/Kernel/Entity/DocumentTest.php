<?php

namespace Drupal\Tests\ncms_ui\Kernel\Entity;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_paragraphs\Entity\Paragraph\DocumentChapter;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Content\Document;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the document entity.
 *
 * @group ncms_ui
 */
class DocumentTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'file',
    'text',
    'user',
    'image',
    'node',
    'taxonomy',
    'media',
    'paragraphs',
    'views',
    'ncms_publisher',
    'ncms_paragraphs',
    'ncms_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');
    $this->installSchema('user', 'users_data');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('media');
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installConfig([
      'system',
      'field',
      'file',
      'user',
      'image',
      'node',
      'taxonomy',
      'media',
      'text',
      'paragraphs',
      'ncms_paragraphs',
      'ncms_ui',
    ]);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

    $this->createContentType(['type' => 'article']);
    $this->createContentType(['type' => 'document']);

    // Create a field with document references for the article type.
    $handler_settings = [
      'target_bundles' => [
        'article' => 'article',
      ],
    ];
    $this->createEntityReferenceField('node', 'document', 'field_articles', 'Articles', 'node', 'default', $handler_settings);

    // Create a field with article references for the document type.
    $handler_settings = [
      'target_bundles' => [
        'document' => 'document',
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_documents', 'Documents', 'node', 'default', $handler_settings);

    // Create the chapter paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'Chapter',
      'id' => 'document_chapter',
    ]);
    $paragraph_type->save();
    $this->addParagraphsField('document_chapter', 'field_articles', 'entity_reference');
    EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'document_chapter',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('text', ['type' => 'entity_reference'])->save();

    $handler_settings = [
      'target_bundles' => [
        'paragraph' => 'paragraph',
      ],
    ];
    $this->createEntityReferenceField('node', 'document', 'field_paragraphs', 'Document structure', 'paragraph', 'default', $handler_settings);
  }

  /**
   * Test the overview url.
   */
  public function testGetOverviewUrl() {
    $document = Document::create([
      'title' => 'Document title',
    ]);
    $this->assertEquals('/admin/content/documents', $document->getOverviewUrl()->toString());
  }

  /**
   * Test the document chapters.
   */
  public function testDocumentChapters() {
    $document = Document::create([
      'title' => 'Document title',
    ]);
    $this->assertEmpty($document->getChapterParagraphs());
  }

  /**
   * Test the references between articles and documents.
   */
  public function testDocumentArticleReferences() {

    // Create an article than can be added to a chapter.
    $article = Article::create([
      'title' => 'Article 1',
    ]);
    $this->assertInstanceOf(Article::class, $article);
    $article->save();

    // Create a chapter that can be added to a document.
    $chapter = Paragraph::create([
      'type' => 'document_chapter',
      'field_articles' => [$article],
    ]);
    $this->assertInstanceOf(DocumentChapter::class, $chapter);
    $chapter->save();

    // Create the document.
    $document = Document::create([
      'title' => 'Document title',
      'field_paragraphs' => [$chapter],
    ]);
    $this->assertInstanceOf(Document::class, $document);
    $document->save();

    // Set the parent chapter manually here for the tests to pass.
    $chapter->setParentEntity($document, 'field_paragraphs');
    $chapter->save();
    $this->assertInstanceOf(Document::class, $chapter->getParentEntity());

    // Save the document again so that the preSave and postSave hooks are
    // executed.
    $document->save();
    // And also reload the article so that we get the currently stored data.
    $article = Article::load($article->id());

    // Confirm the initial population of the referenced articles has been done.
    $this->assertCount(1, $document->get('field_articles')->referencedEntities());
    // Confirm the initial population of the referenced documents has been done.
    $this->assertCount(1, $article->get('field_documents')->referencedEntities());

    // Confirm that the article is able to fetch related documents via the
    // paragraph association.
    $this->assertCount(1, $article->getDocuments());

    // Get the chapters form the document and confirm there is only 1.
    $chapters = $document->getChapterParagraphs();
    $this->assertIsArray($chapters);
    $this->assertCount(1, $chapters);

    // Confirm the chapter has a single article.
    $chapter = reset($chapters);
    $chapter_articles = $chapter->getArticles();
    $this->assertCount(1, $chapter_articles);
    $chapter_article = reset($chapter_articles);
    $this->assertEquals($article->id(), $chapter_article->id());

    // Confirm the documents can be retrieved from the article taken out of the
    // chapter.
    $documents = $chapter_article->getDocuments();
    $this->assertIsArray($documents);
    $this->assertCount(1, $documents);
    $this->assertEquals($document->id(), $documents[array_key_first($documents)]->id());

    // Now add an article to the chapter.
    $article = Article::create([
      'title' => 'Article 1',
    ]);
    $this->assertInstanceOf(Article::class, $article);
    $article->save();
    $chapter->get('field_articles')->appendItem($article);
    $chapter->save();
    $document->save();

    // Confirm document now references 2 articles.
    $this->assertCount(2, $document->get('field_articles')->referencedEntities());

    // Now remove the first article.
    $chapter->get('field_articles')->removeItem(0);
    $chapter->save();
    $document->save();
    // Confirm document now references 1 article.
    $this->assertCount(1, $document->get('field_articles')->referencedEntities());
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addParagraphsField($paragraph_type_name, $field_name, $field_type, $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }

}
