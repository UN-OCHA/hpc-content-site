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
    'filter' => [
      'id' => 'latest_published_version_filter',
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
  $data['node']['places_used_count'] = [
    'title' => t('Places used count'),
    'help' => t('Shows the number of places an entity is used.'),
    'field' => [
      'id' => 'places_used_count_field',
      'no group by' => TRUE,
      'click sortable' => TRUE,
    ],
  ];
  $data['node']['article_count'] = [
    'title' => t('Article count'),
    'help' => t('Shows the number of articles for a document.'),
    'field' => [
      'id' => 'article_count_field',
      'no group by' => TRUE,
      'click sortable' => TRUE,
    ],
  ];
  $data['node']['soft_delete_node'] = [
    'title' => t('Link to soft-delete content'),
    'help' => t('Provide a soft-delete link to the content.'),
    'field' => [
      'id' => 'entity_link_soft_delete',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ],
  ];
  if (array_key_exists('media_field_data', $data)) {
    $data['media_field_data']['soft_delete_media'] = [
      'title' => t('Link to soft-delete Media'),
      'help' => t('Provide a soft-delete link to the Media.'),
      'field' => [
        'id' => 'entity_link_soft_delete',
        'no group by' => TRUE,
        'click sortable' => FALSE,
      ],
    ];
  }
}
