<?php

/**
 * @file
 * Post update functions for NCMS UI.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

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

/**
 * Update links in the admin menu.
 */
function ncms_ui_post_update_update_admin_menu_3() {
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
