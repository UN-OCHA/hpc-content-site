<?php

/**
 * @file
 * Provide views data that is specific to NCMS UI.
 */

/**
 * Implements hook_views_data_alter().
 */
function ncms_ui_views_data_alter(array &$data) {
  // @see https://www.drupal.org/project/drupal/issues/3347343
  $field_name = 'field_documents';
  $data['node__' . $field_name][$field_name . '_target_id']['filter']['id'] = 'entity_reference';

  $data['node']['latest_version'] = [
    'title' => t('Latest version'),
    'help' => t('Shows the latest version for a node.'),
    'field' => [
      'id' => 'latest_version_field',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];
  $data['node']['latest_published_version'] = [
    'title' => t('Latest published version'),
    'help' => t('Shows the latest published version for a node.'),
    'field' => [
      'id' => 'latest_published_version_field',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];
  $data['node']['content_status'] = [
    'title' => t('Content status'),
    'help' => t('Shows the content status.'),
    'field' => [
      'id' => 'content_status_field',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];
}
