<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\ncms_graphql\Wrappers\ContentExportWrapper;
use Drupal\ncms_tags\CommonTaxonomyService;
use Drupal\node\NodeInterface;
use GraphQL\Deferred;

/**
 * Base class for content exports.
 */
abstract class ContentExportBase extends DataProducerPluginBase {

  /**
   * Add tag conditions to an export query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The database query object.
   * @param string[] $tags
   *   The list of tags to search for.
   */
  protected function addTagConditionsToQuery(QueryInterface $query, $tags) {
    // Add conditions to limit to specific tags, either on the content node
    // itself or on the referenced content space.
    $terms = self::getEntityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => self::getCommonTaxonomies()->getCommonTaxonomyBundles(),
      'name' => $tags,
    ]);
    $tag_ids = array_keys($terms);
    foreach ($tag_ids as $tag_id) {
      $and_condition = $query->andConditionGroup();
      $or_condition = $query->orConditionGroup();
      $or_condition->condition('field_content_space.entity.field_computed_tags', $tag_id);
      foreach (self::getCommonTaxonomies()->getCommonTaxonomyFieldNames() as $field_name) {
        $or_condition->condition($field_name, $tag_id);
      }
      $and_condition->condition($or_condition);
      $query->condition($and_condition);
    }
  }

  /**
   * Get the deferred export wrapper.
   *
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   The field context.
   * @param array $tags
   *   Optional aray of tags.
   * @param string $bundle
   *   The bundle for which to export content.
   *
   * @return \GraphQL\Deferred
   *   A promise.
   */
  protected function getDeferredExportWrapper(FieldContext $context, array $tags, string $bundle): Deferred {
    return new Deferred(function () use ($context, $tags, $bundle) {
      $entity_type_manager = self::getEntityTypeManager();
      $entity_type_id = 'node';
      // Add the list cache tags so that the cache entry is purged whenever a
      // new entity of this type is saved.
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $context->addCacheTags($entity_type->getListCacheTags());
      $context->addCacheContexts($entity_type->getListCacheContexts());

      // Build the query.
      $query = $entity_type_manager
        ->getStorage($entity_type_id)
        ->getQuery();
      $query->accessCheck(TRUE);
      $query->condition('type', $bundle);
      $query->condition('status', NodeInterface::PUBLISHED);
      $query->condition('field_computed_tags', NULL, 'IS NOT NULL');
      $query->condition('field_content_space', NULL, 'IS NOT NULL');
      $query->sort('changed', 'DESC');
      if (!empty($tags)) {
        $this->addTagConditionsToQuery($query, $tags);
      }
      return new ContentExportWrapper($query);
    });
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  protected static function getEntityTypeManager(): EntityTypeManagerInterface {
    return \Drupal::entityTypeManager();
  }

  /**
   * Get the common taxonomies service.
   *
   * @return \Drupal\ncms_tags\CommonTaxonomyService
   *   The common taxonomies service.
   */
  protected static function getCommonTaxonomies(): CommonTaxonomyService {
    return \Drupal::service('ncms_tags.common_taxonomies');
  }

}
