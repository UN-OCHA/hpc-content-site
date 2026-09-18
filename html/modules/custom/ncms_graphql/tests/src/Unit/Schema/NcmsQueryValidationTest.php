<?php

namespace Drupal\Tests\ncms_graphql\Unit\Schema;

use Drupal\Tests\UnitTestCase;
use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Validator\DocumentValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests minimal NCMS GraphQL query documents against the schema definition.
 *
 * This is a lightweight schema-contract test. It does not execute resolvers or
 * create Drupal content; the kernel tests cover that slower, higher-confidence
 * path. Here we only parse and validate one small query document for each
 * public root query field so that argument names, argument types, return types,
 * and minimum selectable fields stay compatible with the published schema.
 */
#[Group('ncms_graphql')]
class NcmsQueryValidationTest extends UnitTestCase {

  /**
   * Tests that every public root query has a minimal validation document.
   */
  public function testPublicQueryFieldsHaveRegressionDocuments(): void {
    $fields = array_keys($this->buildSchema()->getQueryType()->getFields());
    // The anchor is only present so the test can build a valid base Query type
    // before appending the module schema files, which use "extend type Query".
    $fields = array_values(array_diff($fields, ['_schemaAnchor']));

    $this->assertSame($fields, array_keys(self::queryDocumentProvider()));
  }

  /**
   * Tests that a minimal query document is valid for the schema.
   *
   * @param string $query
   *   The GraphQL query document to validate.
   */
  #[DataProvider('queryDocumentProvider')]
  public function testQueryDocumentIsValid(string $query): void {
    $errors = DocumentValidator::validate($this->buildSchema(), Parser::parse($query));

    $this->assertSame([], array_map(static fn(Error $error) => $error->getMessage(), $errors));
  }

  /**
   * Provides small query documents covering all public root query fields.
   *
   * @return array<string, array{string}>
   *   The query documents keyed by the root field under test.
   */
  public static function queryDocumentProvider(): array {
    return self::wrapQueryDocuments([
      // Nowdoc strings are intentionally used here so GraphQL variables such
      // as $id and $tags stay literal instead of being interpolated by PHP.
      'connection' => <<<'GRAPHQL'
        query {
          connection
        }
        GRAPHQL,
      'document' => <<<'GRAPHQL'
        query ($id: Int!) {
          document(id: $id) {
            id
            title
          }
        }
        GRAPHQL,
      'documentSearch' => <<<'GRAPHQL'
        query ($title: String) {
          documentSearch(title: $title) {
            count
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      'documentExport' => <<<'GRAPHQL'
        query ($tags: [String]) {
          documentExport(tags: $tags) {
            count
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      'article' => <<<'GRAPHQL'
        query ($id: Int!) {
          article(id: $id) {
            id
            title
          }
        }
        GRAPHQL,
      'articleSearch' => <<<'GRAPHQL'
        query ($title: String) {
          articleSearch(title: $title) {
            count
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      'articleExport' => <<<'GRAPHQL'
        query ($tags: [String]) {
          articleExport(tags: $tags) {
            count
            items {
              id
              title
            }
          }
        }
        GRAPHQL,
      'articleTranslations' => <<<'GRAPHQL'
        query ($id: Int!) {
          articleTranslations(id: $id) {
            id
            title
          }
        }
        GRAPHQL,
      'paragraph' => <<<'GRAPHQL'
        query ($id: Int!) {
          paragraph(id: $id) {
            id
            type
          }
        }
        GRAPHQL,
      'tag' => <<<'GRAPHQL'
        query ($name: String) {
          tag(name: $name) {
            id
            name
            type
          }
        }
        GRAPHQL,
    ]);
  }

  /**
   * Wraps keyed query strings in PHPUnit data provider argument arrays.
   *
   * Keeping queryDocumentProvider() as a field-name => query-string map makes
   * the public query coverage easy to scan. PHPUnit still expects each data
   * provider row to be an array of method arguments, so this helper performs
   * that mechanical wrapping in one place.
   *
   * @param array<string, string> $queries
   *   GraphQL query documents keyed by the root query field under test.
   *
   * @return array<string, array{string}>
   *   The wrapped data provider rows.
   */
  private static function wrapQueryDocuments(array $queries): array {
    return array_map(static fn(string $query) => [$query], $queries);
  }

  /**
   * Builds the NCMS schema from the module schema definition files.
   */
  private function buildSchema(): Schema {
    $module_root = dirname(__DIR__, 4);
    // The production NCMS schema is composable and all public root queries are
    // supplied by extension schema files. The schema builder still needs an
    // initial Query type before it can apply those "extend type Query" blocks.
    $schema = <<<'GRAPHQL'
type Query {
  _schemaAnchor: String
}

GRAPHQL;

    $schema .= file_get_contents($module_root . '/graphql/ncms_schema_extension.base.graphqls');
    $schema .= "\n";
    $schema .= file_get_contents($module_root . '/graphql/ncms_schema_extension.extension.graphqls');

    return BuildSchema::build($schema);
  }

}
