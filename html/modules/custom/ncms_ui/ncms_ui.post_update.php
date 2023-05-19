<?php

/**
 * @file
 * Post update functions for NCMS UI.
 */

/**
 * Set the content space for all nodes.
 */
function ncms_ui_post_update_set_content_space_nodes(&$sandbox) {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
    'vid' => 'content_space',
    'name' => 'Global',
  ]);
  if (empty($terms)) {
    return;
  }
  $term = reset($terms);
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
  foreach ($nodes as $node) {
    if (!$node->hasField('field_content_space') || !$node->get('field_content_space')->isEmpty()) {
      continue;
    }
    $node->get('field_content_space')->setValue([
      'target_id' => $term->id(),
    ]);
    $node->save();
  }
}

/**
 * Set the content space for all users.
 */
function ncms_ui_post_update_set_content_spaces_users(&$sandbox) {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
    'vid' => 'content_space',
    'name' => 'Global',
  ]);
  if (empty($terms)) {
    return;
  }
  $term = reset($terms);
  /** @var \Drupal\user\UserInterface[] $users */
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
  foreach ($users as $user) {
    if ($user->get('field_content_spaces')->isEmpty()) {
      $user->get('field_content_spaces')->setValue([
        'target_id' => $term->id(),
      ]);
    }
    if ($user->hasRole('editor')) {
      $user->removeRole('editor');
      $user->addRole('global_editor');
    }
    $user->save();
  }
}

/**
 * Rebuild the node access grants to take content space changes into account.
 */
function ncms_ui_post_update_rebuild_node_access_for_content_spaces() {
  node_access_rebuild(TRUE);
}

/**
 * Set the moderation state for existing nodes.
 */
function ncms_ui_post_update_set_moderation_state() {
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
  foreach ($nodes as $node) {
    $node->setNewRevision(FALSE);
    $node->setSyncing(TRUE);
    $node->moderation_state->value = $node->isPublished() ? 'published' : 'draft';
    $node->save();
  }
}
