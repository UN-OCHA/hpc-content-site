<?php

/**
 * @file
 * Post update functions for GHO Fields.
 */

/**
 * Copy data from old fields to new fields.
 */
function gho_fields_deploy_post_rename_datawrapper_fields(&$sandbox) {
  /** @var \Drupal\paragraphs\Entity\Paragraph[] $paragraphs */
  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
    'type' => ['interactive_content'],
  ]);
  foreach ($paragraphs as $paragraph) {
    if (!$paragraph->hasField('field_show_datawrapper')) {
      continue;
    }
    if (!$paragraph->hasField('field_show_interactive_content') || !$paragraph->get('field_show_interactive_content')->isEmpty()) {
      continue;
    }
    $paragraph->get('field_show_interactive_content')->setValue([
      'value' => $paragraph->get('field_show_datawrapper')->value,
    ]);
    $paragraph->isSyncing();
    $paragraph->save();
  }
}
