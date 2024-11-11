<?php

/**
 * @file
 * Views hooks specific to NCMS Tags.
 */

/**
 * Implements hook_views_data().
 */
function ncms_tags_views_data() {
  $data = [];
  $data['node__field_computed_tags']['computed_tags_active_tags'] = [
    'help' => t('Display content based on the computed tags. (Active tags)'),
    'group' => t('Content'),
    'real field' => 'field_computed_tags_value',
    'filter' => [
      'title' => t('Computed tags (active tags)'),
      'id' => 'computed_tags_active_tags',
    ],
  ];
  return $data;
}
