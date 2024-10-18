<?php

/**
 * @file
 * Deploy functions for HPC Content Module GraphQL.
 */

/**
 * Set the auto visible flag for all article and document nodes.
 */
function ncms_graphql_deploy_set_auto_visible_flag(&$sandbox) {
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'type' => ['article', 'document'],
  ]);
  foreach ($nodes as $node) {
    if (!$node->hasField('field_automatically_visible') || !$node->get('field_automatically_visible')->isEmpty()) {
      continue;
    }
    $node->get('field_automatically_visible')->setValue([
      'value' => 1,
    ]);
    $node->setSyncing(TRUE);
    $node->save();
  }
}
