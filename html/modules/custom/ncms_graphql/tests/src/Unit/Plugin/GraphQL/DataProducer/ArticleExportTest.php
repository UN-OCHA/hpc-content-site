<?php

namespace Drupal\Tests\ncms_graphql\Unit\Plugin\GraphQL\DataProducer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\ncms_graphql\Plugin\GraphQL\DataProducer\ArticleExport;
use Drupal\ncms_graphql\Wrappers\ContentExportWrapper;
use Drupal\ncms_tags\CommonTaxonomyService;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use GraphQL\Executor\Promise\Adapter\SyncPromiseQueue;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the article export data producer.
 */
#[Group('ncms_graphql')]
class ArticleExportTest extends UnitTestCase {

  /**
   * Tests that the producer builds the expected export query.
   */
  public function testResolveBuildsArticleExportQuery(): void {
    $access_check = NULL;
    $conditions = [];
    $sorts = [];
    $and_group_conditions = [[], []];
    $or_group_conditions = [[], []];

    // The data producer receives a Drupal entity query from storage and builds
    // it through fluent calls. Record only the calls that define the export
    // contract, instead of implementing the entire QueryInterface.
    $query = $this->createMock(QueryInterface::class);
    $query->method('getEntityTypeId')->willReturn('node');
    $query->method('accessCheck')
      ->willReturnCallback(function ($enabled = TRUE) use (&$access_check, $query) {
        $access_check = $enabled;
        return $query;
      });
    $query->method('condition')
      ->willReturnCallback(function ($field, $value = NULL, $operator = NULL, $langcode = NULL) use (&$conditions, $query) {
        $conditions[] = [$field, $value, $operator, $langcode];
        return $query;
      });
    $query->method('sort')
      ->willReturnCallback(function ($field, $direction = 'ASC', $langcode = NULL) use (&$sorts, $query) {
        $sorts[] = [$field, $direction, $langcode];
        return $query;
      });

    $and_groups = [
      $this->createRecordingConditionGroup($and_group_conditions[0]),
      $this->createRecordingConditionGroup($and_group_conditions[1]),
    ];
    $or_groups = [
      $this->createRecordingConditionGroup($or_group_conditions[0]),
      $this->createRecordingConditionGroup($or_group_conditions[1]),
    ];
    // Tag filtering creates one AND group per resolved tag, each wrapping an
    // OR group that matches either the content space tags or direct tag fields.
    $query->expects($this->exactly(2))
      ->method('andConditionGroup')
      ->willReturnOnConsecutiveCalls(...$and_groups);
    $query->expects($this->exactly(2))
      ->method('orConditionGroup')
      ->willReturnOnConsecutiveCalls(...$or_groups);

    $node_storage = $this->createMock(EntityStorageInterface::class);
    $node_storage->method('getQuery')->willReturn($query);

    $tag_one = $this->createMock(EntityTypeInterface::class);
    $tag_one->method('id')->willReturn('11');
    $tag_two = $this->createMock(EntityTypeInterface::class);
    $tag_two->method('id')->willReturn('12');

    $term_storage = $this->createMock(EntityStorageInterface::class);
    $term_storage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'vid' => ['theme', 'major_tags'],
        'name' => ['Shelter'],
      ])
      ->willReturn([
        11 => $tag_one,
        12 => $tag_two,
      ]);

    $node_type = $this->createMock(EntityTypeInterface::class);
    $node_type->method('getListCacheTags')->willReturn(['node_list']);
    $node_type->method('getListCacheContexts')->willReturn(['user.permissions']);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->willReturnMap([
        ['node', $node_storage],
        ['taxonomy_term', $term_storage],
      ]);
    $entity_type_manager->method('getDefinition')
      ->with('node')
      ->willReturn($node_type);

    $common_taxonomies = $this->createMock(CommonTaxonomyService::class);
    $common_taxonomies->method('getCommonTaxonomyBundles')->willReturn(['theme', 'major_tags']);
    $common_taxonomies->method('getCommonTaxonomyFieldNames')->willReturn([
      'field_theme',
      'field_major_tags',
    ]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    $container->set('ncms_tags.common_taxonomies', $common_taxonomies);
    \Drupal::setContainer($container);

    $context = $this->createMock(FieldContext::class);
    $context->expects($this->once())->method('addCacheTags')->with(['node_list']);
    $context->expects($this->once())->method('addCacheContexts')->with(['user.permissions']);

    $producer = new ArticleExport([], 'article_export', []);
    $deferred = $producer->resolve(['Shelter'], $context);
    // GraphQL Deferred callbacks do not run until the synchronous promise queue
    // is drained, so the query is built only after this call.
    SyncPromiseQueue::run();

    $this->assertInstanceOf(ContentExportWrapper::class, $deferred->result);
    $this->assertTrue($access_check);
    $this->assertSame([
      ['type', 'article', NULL, NULL],
      ['status', NodeInterface::PUBLISHED, NULL, NULL],
      ['field_computed_tags', NULL, 'IS NOT NULL', NULL],
      ['field_content_space', NULL, 'IS NOT NULL', NULL],
    ], array_slice($conditions, 0, 4));
    $this->assertSame([['changed', 'DESC', NULL]], $sorts);
    $this->assertSame([
      [$and_groups[0], NULL, NULL, NULL],
      [$and_groups[1], NULL, NULL, NULL],
    ], array_slice($conditions, 4));
    // Inspect one tag group in detail; the second group follows the same
    // construction path with the second resolved tag id.
    $this->assertSame([
      [$or_groups[0], NULL, NULL, NULL],
    ], $and_group_conditions[0]);
    $this->assertSame([
      ['field_content_space.entity.field_computed_tags', 11, NULL, NULL],
      ['field_theme', 11, NULL, NULL],
      ['field_major_tags', 11, NULL, NULL],
    ], $or_group_conditions[0]);
  }

  /**
   * Creates a condition group mock that records chained conditions.
   *
   * @param array $conditions
   *   The condition calls to populate.
   */
  private function createRecordingConditionGroup(array &$conditions): ConditionInterface {
    $group = $this->createMock(ConditionInterface::class);
    // Condition groups are also fluent, so each recorded call returns the same
    // group mock to keep the producer's chained calls intact.
    $group->method('condition')
      ->willReturnCallback(function ($field, $value = NULL, $operator = NULL, $langcode = NULL) use (&$conditions, $group) {
        $conditions[] = [$field, $value, $operator, $langcode];
        return $group;
      });
    return $group;
  }

}
