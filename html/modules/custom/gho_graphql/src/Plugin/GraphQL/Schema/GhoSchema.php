<?php

namespace Drupal\gho_graphql\Plugin\GraphQL\Schema;

use Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema;

/**
 * Defines a composable schema for GHO.
 *
 * @Schema(
 *   id = "ghi_schema",
 *   name = "GHO Schema",
 *   extensions = "gho_schema_extension",
 * )
 */
class GhoSchema extends ComposableSchema {

}
