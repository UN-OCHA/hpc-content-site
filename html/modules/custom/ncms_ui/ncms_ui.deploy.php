<?php

/**
 * @file
 * Deploy functions for NCMS UI.
 */

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\content_moderation\Entity\ContentModerationStateInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Check if an update has already been run.
 *
 * @param string $name
 *   The name of the update.
 *
 * @return bool
 *   TRUE if it has already run, FALSE otherwise.
 */
function ncms_ui_update_already_run($name) {
  return in_array('ncms_ui_post_update_' . $name, \Drupal::keyValue('post_update')->get('existing_updates'));
}

/**
 * Set the moderation state for existing nodes.
 */
function ncms_ui_deploy_set_moderation_state() {
  if (ncms_ui_update_already_run('set_moderation_state')) {
    return;
  }
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
      if (!$revision instanceof ContentInterface || $revision->isDefaultRevision() || $revision->isLatestRevision()) {
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
      if (!$revision instanceof ContentInterface || $revision->isDefaultRevision() || $revision->isLatestRevision() || !$revision->isPublished()) {
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
 * Set the content space for all nodes.
 */
function ncms_ui_deploy_set_content_space_nodes(&$sandbox) {
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
    $node->setSyncing(TRUE);
    $node->save();
  }
}

/**
 * Update links in the admin menu.
 */
function ncms_ui_deploy_update_admin_menu() {
  if (ncms_ui_update_already_run('update_admin_menu')) {
    return;
  }
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
function ncms_ui_deploy_set_content_space_nodes_orphaned(&$sandbox) {
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
    $node->setSyncing(TRUE);
    $node->save();
  }
}

/**
 * Set the content space for all media items.
 */
function ncms_ui_deploy_set_content_space_media(&$sandbox) {
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
    'vid' => 'content_space',
    'name' => 'Global',
  ]);
  if (empty($terms)) {
    return;
  }
  $term = reset($terms);
  /** @var \Drupal\media\MediaInterface[] $entities */
  $entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple();
  foreach ($entities as $entity) {
    if (!$entity->hasField('field_content_space') || !$entity->get('field_content_space')->isEmpty()) {
      continue;
    }
    $entity->get('field_content_space')->setValue([
      'target_id' => $term->id(),
    ]);
    $entity->setSyncing(TRUE);
    $entity->save();
  }
}

/**
 * Remove obsolete links from the admin menu.
 */
function ncms_ui_deploy_remove_obsolete_admin_menu_links() {
  /** @var \Drupal\menu_link_content\Entity\MenuLinkContent[] $links */
  $links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties([
    'menu_name' => 'admin',
    'link__uri' => 'internal:/admin/content/achievements',
  ]);
  foreach ($links as $menu_link) {
    $menu_link->delete();
  }
}

/**
 * Correct the changed date for content.
 *
 * The changed date got corrupted by
 * ncms_graphql_deploy_set_auto_visible_flag().
 */
function ncms_ui_deploy_correct_changed_date(&$sandbox) {
  /** @var \Drupal\Node\NodeStorageInterface $node_storage */
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');

  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = $node_storage->loadByProperties([
    'type' => ['article', 'document'],
  ]);

  foreach ($nodes as $node) {
    if (!$node instanceof ContentInterface) {
      continue;
    }

    // Get all revision ids.
    $revision_ids = $node_storage->revisionIds($node);
    if (count($revision_ids) < 2) {
      continue;
    }
    $revision_ids = array_values(array_reverse($revision_ids));

    /** @var \Drupal\ncms_ui\Entity\ContentInterface $revision */
    $revision = $node_storage->loadRevision($revision_ids[0]);
    if (!$revision) {
      continue;
    }

    if ($revision->getChangedTime() != 1698930576) {
      continue;
    }

    // Update the changed date of the current revision.
    $revision->setChangedTime($revision->getRevisionCreationTime());
    $revision->setNewRevision(FALSE);
    $revision->setSyncing(TRUE);
    $revision->save();

    // Update the changed date of the node.
    $node->setChangedTime($revision->getRevisionCreationTime());
    $node->setNewRevision(FALSE);
    $node->setSyncing(TRUE);
    $node->save();
  }
}

/**
 * Set the content space for all users.
 */
function ncms_ui_deploy_set_collapsible_field_value(&$sandbox) {
  /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
    'type' => 'sub_article',
  ]);
  if (empty($paragraphs)) {
    return;
  }
  foreach ($paragraphs as $paragraph) {
    $paragraph->get('field_collapsible')->applyDefaultValue();
    $paragraph->save();
  }
}
