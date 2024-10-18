<?php

/**
 * @file
 * Deploy functions for HPC Content Module Tags.
 */

use Drupal\taxonomy\TermInterface;

/**
 * Setup the country vocabulary.
 */
function ncms_tags_deploy_setup_country_vocabulary(&$sandbox) {
  $countries = [
    'Afghanistan',
    'Burkina Faso',
    'Burundi',
    'Cameroon',
    'Central African Republic',
    'Chad',
    'Colombia',
    'Democratic Republic of the Congo',
    'El Salvador',
    'Ethiopia',
    'Guatemala',
    'Haiti',
    'Honduras',
    'Iraq',
    'Kenya',
    'Lebanon',
    'Libya',
    'Madagascar',
    'Malawi',
    'Mali',
    'Mozambique',
    'Myanmar',
    'Niger',
    'Nigeria',
    'Occupied Palestinian Territory',
    'Pakistan',
    'Somalia',
    'South Sudan',
    'Sudan',
    'Syrian Arab Republic',
    'Ukraine',
    'Venezuela',
    'Yemen',
    'World',
  ];
  $not_migrated = [];
  foreach ($countries as $key => $country) {
    $result = ncms_tags_create_and_migrate('country', $country, $key, 'field_country');
    if ($result === FALSE) {
      $not_migrated[] = $country;
    }
  }
  if (count($not_migrated)) {
    return t('Processed @processed country tags, skipped migration for these tags: @not_migrated', [
      '@processed' => count($countries),
      '@not_migrated' => implode(', ', $not_migrated),
    ]);
  }
  else {
    return t('Processed @processed country tags', [
      '@processed' => count($countries),
    ]);
  }
}

/**
 * Setup the document type vocabulary.
 */
function ncms_tags_deploy_setup_document_type_vocabulary(&$sandbox) {
  $document_types = [
    'HNO',
    'HRP',
    'HNRP',
    'GHO',
    [
      'name' => 'GHO Monthly',
      'alternatives' => ['GHO Monthly update'],
    ],
    'Flash Appeal',
    'Other plan',
  ];
  $not_migrated = [];
  foreach ($document_types as $key => $document_type) {
    $document_type_name = is_array($document_type) ? $document_type['name'] : $document_type;
    $document_type_alternatives = is_array($document_type) ? $document_type['alternatives'] : [];
    $result = ncms_tags_create_and_migrate('document_type', $document_type_name, $key, 'field_document_type', $document_type_alternatives);
    if ($result === FALSE) {
      $not_migrated[] = $document_type_name;
    }
  }
  if (count($not_migrated)) {
    return t('Processed @processed document type tags, skipped migration for these tags: @not_migrated', [
      '@processed' => count($document_types),
      '@not_migrated' => implode(', ', $not_migrated),
    ]);
  }
  else {
    return t('Processed @processed document type tags', [
      '@processed' => count($document_types),
    ]);
  }
}

/**
 * Create and migrate a term for the given vocabulary.
 *
 * @param string $vid
 *   The machine name of the vocabulary.
 * @param string $term_name
 *   The term name.
 * @param int $weight
 *   The weight of the term in the vocabulary.
 * @param string $field_name
 *   The field name of the term on article nodes.
 * @param string[] $alternative_names
 *   An optional list of alternative names for the given term.
 *
 * @return bool
 *   The result state of the operation.
 */
function ncms_tags_create_and_migrate($vid, $term_name, $weight, $field_name, $alternative_names = []) {
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  /** @var \Drupal\taxonomy\TermInterface $term */
  $term = $term_storage->create([
    'vid' => $vid,
    'name' => $term_name,
    'weight' => $weight,
  ]);
  $term->save();

  /** @var \Drupal\taxonomy\TermInterface $tag */
  $tag = $term_storage->loadByProperties([
    'vid' => 'major_tags',
    'name' => $term->getName(),
  ]);
  $tag = is_array($tag) ? reset($tag) : $tag;

  /** @var \Drupal\taxonomy\TermInterface[] $alternative_terms */
  $alternative_terms = !empty($alternative_names) ? $term_storage->loadByProperties([
    'vid' => 'major_tags',
    'name' => $alternative_names,
  ]) : [];

  if (!$tag instanceof TermInterface && empty($alternative_terms)) {
    return FALSE;
  }

  $tag_ids = [];
  if ($tag) {
    $tag_ids[] = $tag->id();
  }
  if (!empty($alternative_terms)) {
    $alternative_tag_ids = array_map(function (TermInterface $_term) {
      return $_term->id();
    }, $alternative_terms);
    $tag_ids = array_merge($tag_ids, $alternative_tag_ids);
  }

  /** @var \Drupal\node\NodeInterface[] $nodes */
  $nodes = $node_storage->loadByProperties([
    'type' => ['article', 'document'],
    'field_tags' => $tag_ids,
  ]);
  foreach ($nodes as $node) {
    $tags = $node->get('field_tags')->getValue();
    $tags = array_filter($tags, function ($_tag) use ($tag_ids) {
      return !in_array($_tag['target_id'], $tag_ids);
    });
    $node->get('field_tags')->setValue($tags);
    $node->get($field_name)->setValue($term);
    $node->setNewRevision(FALSE);
    $node->setSyncing(TRUE);
    $node->save();
  }

  if ($tag) {
    $tag->delete();
  }
  foreach ($alternative_terms as $term) {
    $term->delete();
  }
  return TRUE;
}
