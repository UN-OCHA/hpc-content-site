<?php

/**
 * @file
 * Views plugin definitions.
 */

/**
 * Implements hook_views_data().
 */
function gho_fields_views_data() {
  $data = [];
  foreach (['media', 'node', 'taxonomy_term_data'] as $base_table) {
    $data[$base_table]['gho_translation_links'] = [
      'field' => [
        'title' => t('GHO: translation links'),
        'help' => t('Provide links to edit, view or create a translation.'),
        'id' => 'gho_translation_links',
      ],
    ];
  }
  return $data;
}
