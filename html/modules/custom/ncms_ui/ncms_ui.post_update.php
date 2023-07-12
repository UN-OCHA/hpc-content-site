<?php

/**
 * @file
 * Post update functions for NCMS UI.
 */

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;

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
    $node->isSyncing();
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
  /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = $node_storage->loadMultiple();
  foreach ($nodes as $node) {
    $moderation_state = $node->isPublished() ? 'published' : 'draft';
    $entity_revision_id = $node->getRevisionId();
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($node);
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('content_moderation_state');

    if (!($content_moderation_state instanceof ContentModerationStateInterface)) {
      $content_moderation_state = $storage->create([
        'content_entity_type_id' => $node->getEntityTypeId(),
        'content_entity_id' => $node->id(),
        // Make sure that the moderation state entity has the same language code
        // as the moderated entity.
        'langcode' => $node->language()->getId(),
      ]);
      $content_moderation_state->workflow->target_id = 'article_workflow';
    }
    $content_moderation_state->set('content_entity_revision_id', $entity_revision_id);
    $content_moderation_state->set('moderation_state', $moderation_state);
    $content_moderation_state->set('uid', $node->getRevisionUserId());
    ContentModerationState::updateOrCreateFromEntity($content_moderation_state);

    // Also iterate over all revisions for this node and update the moderation
    // state.
    $revision_ids = $node_storage->revisionIds($node);
    /** @var \Drupal\node\NodeInterface[] $revisions */
    $revisions = $node_storage->loadMultipleRevisions($revision_ids);
    foreach ($revisions as $revision) {
      if (!$revision instanceof ContentBase || $revision->isDefaultRevision() || $revision->isLatestRevision()) {
        continue;
      }

      $revision_state = 'draft';
      $revision_moderation_state = $storage->createRevision($content_moderation_state, FALSE);
      $revision_moderation_state->set('content_entity_revision_id', $revision->getRevisionId());
      $revision_moderation_state->set('moderation_state', $revision_state);
      $revision_moderation_state->set('uid', $revision->getRevisionUserId());
      ContentModerationState::updateOrCreateFromEntity($revision_moderation_state);
    }
  }

  // Update the revision status for each node. For some reason, this needs to
  // be done in a separate step, otherwise there are duplicate key errors.
  foreach ($nodes as $node) {
    // Also iterate over all revisions for this node and update the moderation
    // state.
    $revision_ids = $node_storage->revisionIds($node);
    /** @var \Drupal\node\NodeInterface[] $revisions */
    $revisions = $node_storage->loadMultipleRevisions($revision_ids);
    foreach ($revisions as $revision) {
      if (!$revision instanceof ContentBase || $revision->isDefaultRevision() || $revision->isLatestRevision() || !$revision->isPublished()) {
        continue;
      }
      $node_storage->updateRevisionStatus($revision, NodeInterface::NOT_PUBLISHED, FALSE);
    }
  }

  // Update the revision_translation_affected field so that all revisions show
  // up on the versions tab.
  \Drupal::database()->update('node_field_revision')
    ->fields(['revision_translation_affected' => 1])
    ->execute();
}

/**
 * Update links in the admin menu.
 */
function ncms_ui_post_update_update_admin_menu() {
  /** @var \Drupal\Core\Menu\MenuLinkManager $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple(NULL);
  $links = $menu_link_manager->loadLinksByRoute('node.add_page', [], 'admin');
  foreach ($node_types as $node_type) {
    $links = $links + $menu_link_manager->loadLinksByRoute('node.add', ['node_type' => $node_type->id()], 'admin');
  }
  foreach ($links as $menu_link) {
    $menu_link->updateLink([
      'enabled' => FALSE,
    ], TRUE);
  }

  /** @var \Drupal\views\Entity\View $view */
  $view = \Drupal::entityTypeManager()
    ->getStorage('view')
    ->load('content');
  $displays = $view->get('display');
  uasort($displays, function ($display_a, $display_b) {
    return $display_a['position'] - $display_b['position'];
  });

  foreach (array_values($displays) as $weight => $display) {
    if ($display['display_plugin'] != 'page') {
      continue;
    }
    $options = $display['display_options'];
    if (($options['enabled'] ?? NULL) === FALSE) {
      continue;
    }
    MenuLinkContent::create([
      'title' => $options['menu']['title'],
      'link' => [
        'uri' => 'internal:/' . ltrim($options['path'], '/'),
      ],
      'menu_name' => 'admin',
      'parent' => $options['menu']['parent'],
      'weight' => $weight,
    ])->save();
  }
}

/**
 * Set the content space for all nodes that reference a deleted term.
 */
function ncms_ui_post_update_set_content_space_nodes_orphaned(&$sandbox) {
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
    if (!$node->hasField('field_content_space')) {
      continue;
    }
    $referenced_terms = $node->get('field_content_space')->referencedEntities();
    if (!empty($referenced_terms)) {
      continue;
    }
    $node->get('field_content_space')->setValue([
      'target_id' => $term->id(),
    ]);
    $node->isSyncing();
    $node->save();
  }
}
