<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;

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
   * @param string[] $tags
   *   Optional tags to search for.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $context
   *   A context object.
   *
   * @return \GraphQL\Deferred
   *   A promise.
   */
  public function resolve(array $tags, FieldContext $context) {
    return $this->getDeferredExportWrapper($context, $tags, 'article');
  }

}
