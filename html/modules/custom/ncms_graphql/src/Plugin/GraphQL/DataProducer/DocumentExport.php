<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;

/**
 * Load all exportable documents.
 *
 * @DataProducer(
 *   id = "document_export",
 *   name = @Translation("Load all exportable documents"),
 *   description = @Translation("Loads all exportable documents."),
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
class DocumentExport extends ContentExportBase {

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
    return $this->getDeferredExportWrapper($context, $tags, 'document');
  }

}
