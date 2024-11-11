<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\TranslatableInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\ncms_graphql\Wrappers\QueryConnection;
use Drupal\node\NodeInterface;
use GraphQL\Deferred;

/**
 * Load all exportable articles.
 *
 * @DataProducer(
 *   id = "article_export",
 *   name = @Translation("Load all exportable articles"),
 *   description = @Translation("Loads all exportable articles."),
 *   produces = @ContextDefinition("entities",
 *     label = @Translation("Entities")
 *   ),
 *   consumes = {
 *     "tags" = @ContextDefinition("string",
 *       label = @Translation("Tags"),
 *       multiple = TRUE,
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class ArticleExport extends ContentExportBase {

  /**
   * Resolver.
   *
   * @param array $tags
   *   The tags to search for.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   A context object.
   *
   * @return \GraphQL\Deferred
   *   A promise.
   */
  public function resolve(array $tags = NULL, FieldContext $context) {
    return new Deferred(function () use ($tags, $context) {
      $type = 'node';

      // Add the list cache tags so that the cache entry is purged whenever a
      // new entity of this type is saved.
      $entity_type = $this->entityTypeManager->getDefinition($type);
      $context->addCacheTags($entity_type->getListCacheTags());
      $context->addCacheContexts($entity_type->getListCacheContexts());

      // Load the buffered entities.
      $query = $this->entityTypeManager
        ->getStorage($type)
        ->getQuery();
      $query->accessCheck(TRUE);
      $query->condition('type', 'article');
      $query->condition('status', NodeInterface::PUBLISHED);
      $query->condition('field_computed_tags', NULL, 'IS NOT NULL');
      $query->condition('field_content_space', NULL, 'IS NOT NULL');
      $query->sort('changed', 'DESC');
      if (!empty($tags)) {
        $this->addTagConditionsToQuery($query, $tags);
      }
      return new QueryConnection($query);
    });
  }

}
