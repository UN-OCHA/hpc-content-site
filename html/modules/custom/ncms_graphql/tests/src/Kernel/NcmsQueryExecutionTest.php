<?php

namespace Drupal\Tests\ncms_graphql\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\graphql\GraphQL\Execution\ExecutionResult;
use Drupal\ncms_graphql\Plugin\GraphQL\SchemaExtension\NcmsSchemaExtension;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use GraphQL\Server\OperationParams;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the NCMS GraphQL root queries against dummy content.
 */
#[Group('ncms_graphql')]
class NcmsQueryExecutionTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'language',
    'content_translation',
    'node',
    'taxonomy',
    'file',
    'graphql',
    'layout_discovery',
    'entity_reference_revisions',
    'paragraphs',
    'layout_paragraphs',
    'ncms_tags',
    'ncms_paragraphs',
    'ncms_graphql',
  ];

  /**
   * Dummy article node.
   *
   * @var \Drupal\node\Entity\Node
   */
  private Node $article;

  /**
   * Dummy document node.
   *
   * @var \Drupal\node\Entity\Node
   */
  private Node $document;

  /**
   * Dummy document chapter paragraph.
   *
   * @var \Drupal\paragraphs\Entity\Paragraph
   */
  private Paragraph $chapter;

  /**
   * Dummy tag term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private Term $tag;

  /**
   * Dummy content space term.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  private Term $contentSpace;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installConfig(['filter']);

    $this->createContentModel();
    $this->setUpNcmsSchema();
    $this->createDummyContent();
  }

  /**
   * Tests the connection health-check query.
   */
  public function testConnectionQuery(): void {
    // Confirms the public health-check field is wired to the connection status
    // data producer and returns the fixed status expected by clients.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query {
          connection
        }
        GRAPHQL,
      [],
      ['connection' => 'connected'],
    );
  }

  /**
   * Tests node, search, and export queries for articles.
   */
  public function testArticleQueries(): void {
    // Confirms an article can be loaded by ID and that scalar fields, computed
    // tags, language metadata, force-update state, and content-space references
    // resolve from a real node fixture.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($id: Int!) {
          article(id: $id) {
            id
            title
            title_short
            summary
            tags
            autoVisible
            forceUpdate
            language {
              id
              name
            }
            content_space {
              id
              title
              tags
            }
          }
        }
        GRAPHQL,
      ['id' => (int) $this->article->id()],
      [
        'article' => [
          'id' => (int) $this->article->id(),
          'title' => 'Dummy article',
          'title_short' => 'Article short',
          'summary' => 'Article summary',
          'tags' => ['Shelter', 'Operations'],
          'autoVisible' => 1,
          'forceUpdate' => 1710000100,
          'language' => [
            'id' => 'en',
            'name' => 'English',
          ],
          'content_space' => [
            'id' => (int) $this->contentSpace->id(),
            'title' => 'Operations',
            'tags' => ['Operations'],
          ],
        ],
      ],
    );

    // Confirms articleSearch matches by title and returns the wrapper fields
    // used by clients: count, ids, metadata, and loaded item entities.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($title: String) {
          articleSearch(title: $title) {
            count
            ids
            metaData {
              id
              title
              title_short
              summary
              autoVisible
              forceUpdate
            }
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      ['title' => 'Dummy'],
      [
        'articleSearch' => [
          'count' => 1,
          'ids' => [(int) $this->article->id()],
          'metaData' => [
            [
              'id' => (int) $this->article->id(),
              'title' => 'Dummy article',
              'title_short' => 'Article short',
              'summary' => 'Article summary',
              'autoVisible' => 1,
              'forceUpdate' => 1710000100,
            ],
          ],
          'items' => [
            [
              'id' => (int) $this->article->id(),
              'title' => 'Dummy article',
            ],
          ],
        ],
      ],
    );

    // Confirms articleExport applies tag filtering through the common taxonomy
    // fields and returns export wrapper counts, IDs, and loaded articles.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($tags: [String]) {
          articleExport(tags: $tags) {
            count
            ids
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      ['tags' => ['Shelter']],
      [
        'articleExport' => [
          'count' => 1,
          'ids' => [(int) $this->article->id()],
          'items' => [
            [
              'id' => (int) $this->article->id(),
              'title' => 'Dummy article',
            ],
          ],
        ],
      ],
    );
  }

  /**
   * Tests node, search, and export queries for documents.
   */
  public function testDocumentQueries(): void {
    // Confirms a document can be loaded by ID with its scalar fields, tags,
    // content space, force-update state, and nested chapter/article structure.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($id: Int!) {
          document(id: $id) {
            id
            title
            title_short
            summary
            tags
            autoVisible
            forceUpdate
            content_space {
              title
            }
            chapters {
              id
              title
              title_short
              summary
              hidden
              tags
              articles {
                id
                title
              }
            }
          }
        }
        GRAPHQL,
      ['id' => (int) $this->document->id()],
      [
        'document' => [
          'id' => (int) $this->document->id(),
          'title' => 'Dummy document',
          'title_short' => 'Document short',
          'summary' => 'Document summary',
          'tags' => ['Shelter'],
          'autoVisible' => 1,
          'forceUpdate' => 1710000200,
          'content_space' => [
            'title' => 'Operations',
          ],
          'chapters' => [
            [
              'id' => (int) $this->chapter->id(),
              'title' => 'Dummy chapter',
              'title_short' => 'Chapter short',
              'summary' => 'Chapter summary',
              'hidden' => FALSE,
              'tags' => ['Shelter'],
              'articles' => [
                [
                  'id' => (int) $this->article->id(),
                  'title' => 'Dummy article',
                ],
              ],
            ],
          ],
        ],
      ],
    );

    // Confirms documentSearch matches by title and returns the wrapper fields
    // needed to page through matching document entities.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($title: String) {
          documentSearch(title: $title) {
            count
            ids
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      ['title' => 'Dummy'],
      [
        'documentSearch' => [
          'count' => 1,
          'ids' => [(int) $this->document->id()],
          'items' => [
            [
              'id' => (int) $this->document->id(),
              'title' => 'Dummy document',
            ],
          ],
        ],
      ],
    );

    // Confirms documentExport applies the same tag-filtering path as articles
    // while restricting results to published document nodes.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($tags: [String]) {
          documentExport(tags: $tags) {
            count
            ids
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      ['tags' => ['Shelter']],
      [
        'documentExport' => [
          'count' => 1,
          'ids' => [(int) $this->document->id()],
          'items' => [
            [
              'id' => (int) $this->document->id(),
              'title' => 'Dummy document',
            ],
          ],
        ],
      ],
    );
  }

  /**
   * Tests auxiliary entity lookup queries.
   */
  public function testAuxiliaryEntityQueries(): void {
    // Confirms articleTranslations returns all available translations for a
    // translated article and exposes the language code for each translation.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($id: Int!) {
          articleTranslations(id: $id) {
            id
            title
            language {
              id
            }
          }
        }
        GRAPHQL,
      ['id' => (int) $this->article->id()],
      [
        'articleTranslations' => [
          [
            'id' => (int) $this->article->id(),
            'title' => 'Dummy article',
            'language' => [
              'id' => 'en',
            ],
          ],
          [
            'id' => (int) $this->article->id(),
            'title' => 'Dummy article FR',
            'language' => [
              'id' => 'fr',
            ],
          ],
        ],
      ],
    );

    // Confirms paragraph lookup returns fields resolved through the paragraph
    // bundle class and entity configuration data producer.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($id: Int!) {
          paragraph(id: $id) {
            id
            type
            typeLabel
            promoted
            configuration
          }
        }
        GRAPHQL,
      ['id' => (int) $this->chapter->id()],
      [
        'paragraph' => [
          'id' => (int) $this->chapter->id(),
          'type' => 'document_chapter',
          'typeLabel' => 'Document chapter',
          'promoted' => FALSE,
          'configuration' => '{  }',
        ],
      ],
    );

    // Confirms tag lookup resolves a unique taxonomy term by name across the
    // supported tag vocabularies and exposes its vocabulary as the type.
    $this->assertQueryData(
      <<<'GRAPHQL'
        query ($name: String) {
          tag(name: $name) {
            id
            name
            type
          }
        }
        GRAPHQL,
      ['name' => 'Shelter'],
      [
        'tag' => [
          'id' => (int) $this->tag->id(),
          'name' => 'Shelter',
          'type' => 'theme',
        ],
      ],
    );
  }

  /**
   * Executes a GraphQL query and asserts the returned data.
   *
   * @param string $query
   *   The GraphQL query to execute.
   * @param array $variables
   *   The query variables.
   * @param array $expected_data
   *   The expected data section.
   */
  private function assertQueryData(string $query, array $variables, array $expected_data): void {
    $execution_result = $this->executeQuery($query, $variables);
    $result = $execution_result->toArray();

    $this->assertArrayNotHasKey('errors', $result, $this->formatExecutionErrors($execution_result));
    $this->assertSame($expected_data, $result['data']);
  }

  /**
   * Executes a GraphQL query against the NCMS server.
   */
  private function executeQuery(string $query, array $variables = []): ExecutionResult {
    return $this->server->executeOperation(OperationParams::create([
      'query' => $query,
      'variables' => $variables,
    ]));
  }

  /**
   * Formats execution errors including unmasked previous exceptions.
   */
  private function formatExecutionErrors(ExecutionResult $result): string {
    $messages = [];
    foreach ($result->errors as $error) {
      $previous = $error->getPrevious();
      $messages[] = $previous ? sprintf(
        '%s Previous: %s in %s:%d',
        $error->getMessage(),
        $previous->getMessage(),
        $previous->getFile(),
        $previous->getLine(),
      ) : $error->getMessage();
    }
    return implode("\n", $messages);
  }

  /**
   * Sets up the real NCMS schema extension on a lightweight base schema.
   */
  private function setUpNcmsSchema(): void {
    $schema = <<<'GRAPHQL'
      type Query {
        _schemaAnchor: String
      }
      GRAPHQL;

    $extension = NcmsSchemaExtension::create(
      $this->container,
      [],
      'ncms_schema_extension',
      [
        'class' => NcmsSchemaExtension::class,
        'provider' => 'ncms_graphql',
      ],
    );

    $this->setUpSchema($schema, 'ncms_schema', [], [$extension]);
  }

  /**
   * Creates the minimal bundles and fields touched by the query resolvers.
   */
  private function createContentModel(): void {
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();
    NodeType::create([
      'type' => 'document',
      'name' => 'Document',
    ])->save();

    Vocabulary::create([
      'vid' => 'content_space',
      'name' => 'Content space',
    ])->save();
    Vocabulary::create([
      'vid' => 'theme',
      'name' => 'Theme',
    ])->save();
    foreach (['document_type', 'country', 'year', 'month'] as $vid) {
      Vocabulary::create([
        'vid' => $vid,
        'name' => $vid,
      ])->save();
    }

    ParagraphsType::create([
      'id' => 'document_chapter',
      'label' => 'Document chapter',
    ])->save();

    ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
      ->setDefaultLangcode('en')
      ->setLanguageAlterable(TRUE)
      ->save();
    \Drupal::service('content_translation.manager')->setEnabled('node', 'article', TRUE);

    $this->createFieldStorage('node', 'field_short_title', 'string');
    $this->createFieldStorage('node', 'field_summary', 'string_long');
    $this->createFieldStorage('node', 'field_automatically_visible', 'boolean');
    $this->createFieldStorage('node', 'field_computed_tags', 'string');
    $this->createFieldStorage('node', 'field_content_space', 'entity_reference', [
      'target_type' => 'taxonomy_term',
    ]);
    foreach ([
      'field_document_type',
      'field_country',
      'field_year',
      'field_month',
      'field_theme',
    ] as $field_name) {
      $this->createFieldStorage('node', $field_name, 'entity_reference', [
        'target_type' => 'taxonomy_term',
      ], FieldStorageConfig::CARDINALITY_UNLIMITED);
    }
    $this->createFieldStorage('node', 'field_paragraphs', 'entity_reference_revisions', [
      'target_type' => 'paragraph',
    ], FieldStorageConfig::CARDINALITY_UNLIMITED);

    foreach (['article', 'document'] as $bundle) {
      $this->createField('node', $bundle, 'field_short_title');
      $this->createField('node', $bundle, 'field_summary');
      $this->createField('node', $bundle, 'field_automatically_visible');
      $this->createField('node', $bundle, 'field_computed_tags');
      $this->createField('node', $bundle, 'field_content_space');
      foreach ([
        'field_document_type',
        'field_country',
        'field_year',
        'field_month',
        'field_theme',
      ] as $field_name) {
        $this->createField('node', $bundle, $field_name);
      }
    }
    $this->createField('node', 'document', 'field_paragraphs');

    $this->createFieldStorage('taxonomy_term', 'field_computed_tags', 'string');
    $this->createField('taxonomy_term', 'content_space', 'field_computed_tags');

    $this->createFieldStorage('paragraph', 'field_title', 'string');
    $this->createFieldStorage('paragraph', 'field_short_title', 'string');
    $this->createFieldStorage('paragraph', 'field_summary', 'string_long');
    $this->createFieldStorage('paragraph', 'field_hide_from_navigation', 'boolean');
    $this->createFieldStorage('paragraph', 'field_computed_tags', 'string');
    $this->createFieldStorage('paragraph', 'field_articles', 'entity_reference', [
      'target_type' => 'node',
    ], FieldStorageConfig::CARDINALITY_UNLIMITED);

    foreach ([
      'field_title',
      'field_short_title',
      'field_summary',
      'field_hide_from_navigation',
      'field_computed_tags',
      'field_articles',
    ] as $field_name) {
      $this->createField('paragraph', 'document_chapter', $field_name);
    }
  }

  /**
   * Creates dummy entities used by the GraphQL query execution tests.
   */
  private function createDummyContent(): void {
    $this->tag = Term::create([
      'vid' => 'theme',
      'name' => 'Shelter',
    ]);
    $this->tag->save();

    $this->contentSpace = Term::create([
      'vid' => 'content_space',
      'name' => 'Operations',
      'field_computed_tags' => 'Operations',
    ]);
    $this->contentSpace->save();

    $this->article = Node::create([
      'type' => 'article',
      'title' => 'Dummy article',
      'status' => 1,
      'created' => 1710000001,
      'changed' => 1710000002,
      'field_short_title' => 'Article short',
      'field_summary' => 'Article summary',
      'field_automatically_visible' => 1,
      'field_computed_tags' => 'Shelter,Operations',
      'field_content_space' => $this->contentSpace,
      'field_theme' => [$this->tag],
      'force_update' => 1710000100,
    ]);
    $this->article->save();
    $this->article->addTranslation('fr', [
      'title' => 'Dummy article FR',
      'field_short_title' => 'Article court',
      'field_summary' => 'Article summary FR',
      'field_automatically_visible' => 1,
      'field_computed_tags' => 'Shelter,Operations',
      'field_content_space' => $this->contentSpace,
      'field_theme' => [$this->tag],
      'force_update' => 1710000100,
    ])->save();

    $this->chapter = Paragraph::create([
      'type' => 'document_chapter',
      'field_title' => 'Dummy chapter',
      'field_short_title' => 'Chapter short',
      'field_summary' => 'Chapter summary',
      'field_hide_from_navigation' => 0,
      'field_computed_tags' => 'Shelter',
      'field_articles' => [$this->article],
    ]);
    $this->chapter->save();

    $this->document = Node::create([
      'type' => 'document',
      'title' => 'Dummy document',
      'status' => 1,
      'created' => 1710000011,
      'changed' => 1710000012,
      'field_short_title' => 'Document short',
      'field_summary' => 'Document summary',
      'field_automatically_visible' => 1,
      'field_computed_tags' => 'Shelter',
      'field_content_space' => $this->contentSpace,
      'field_theme' => [$this->tag],
      'field_paragraphs' => [$this->chapter],
      'force_update' => 1710000200,
    ]);
    $this->document->save();
  }

  /**
   * Creates field storage.
   */
  private function createFieldStorage(string $entity_type, string $field_name, string $type, array $settings = [], int $cardinality = 1): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'settings' => $settings,
      'cardinality' => $cardinality,
    ])->save();
  }

  /**
   * Creates a bundle field instance.
   */
  private function createField(string $entity_type, string $bundle, string $field_name): void {
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $field_name,
    ])->save();
  }

}
