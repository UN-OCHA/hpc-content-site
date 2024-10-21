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
    'Åland Islands',
    'Albania',
    'Algeria',
    'American Samoa',
    'Andorra',
    'Angola',
    'Anguilla (United Kingdom)',
    'Antarctica',
    'Antigua and Barbuda',
    'Argentina',
    'Armenia',
    'Aruba (Netherlands)',
    'Australia',
    'Austria',
    'Azerbaijan',
    'Bahamas',
    'Bahrain',
    'Bangladesh',
    'Barbados',
    'Belarus',
    'Belgium',
    'Belize',
    'Benin',
    'Bermuda',
    'Bhutan',
    'Bolivia, Plurinational State of',
    'Bonaire, Saint Eustatius and Saba (The Netherlands)',
    'Bosnia and Herzegovina',
    'Botswana',
    'Bouvet Island',
    'Brazil',
    'British Indian Ocean Territory',
    'Brunei Darussalam',
    'Bulgaria',
    'Burkina Faso',
    'Burundi',
    'Cambodia',
    'Cameroon',
    'Canada',
    'Cape Verde',
    'Cayman Islands',
    'Central African Republic',
    'Chad',
    'Chile',
    'China',
    'Christmas Island',
    'Cocos (Keeling) Islands',
    'Colombia',
    'Comoros',
    'Congo',
    'Congo, The Democratic Republic of the',
    'Cook Islands',
    'Costa Rica',
    'Côte d\'Ivoire',
    'Croatia',
    'Cuba',
    'Curaçao (Netherlands)',
    'Cyprus',
    'Czech Republic',
    'Denmark',
    'Djibouti',
    'Dominica',
    'Dominican Republic',
    'Ecuador',
    'Egypt',
    'El Salvador',
    'Equatorial Guinea',
    'Eritrea',
    'Estonia',
    'Eswatini',
    'Ethiopia',
    'Falkland Islands (Malvinas)',
    'Faroe Islands',
    'Fiji',
    'Finland',
    'France',
    'French Guiana',
    'French Polynesia',
    'French Southern Territories',
    'Gabon',
    'Gambia',
    'Georgia',
    'Germany',
    'Ghana',
    'Gibraltar',
    'Global',
    'Greece',
    'Greenland',
    'Grenada',
    'Guadeloupe (France)',
    'Guam',
    'Guatemala',
    'Guernsey',
    'Guinea',
    'Guinea-Bissau',
    'Guyana',
    'Haiti',
    'Heard Island and McDonald Islands',
    'Holy See (Vatican City State)',
    'Honduras',
    'Hong Kong',
    'Hungary',
    'Iceland',
    'India',
    'Indonesia',
    'Iran, Islamic Republic of',
    'Iraq',
    'Ireland',
    'Isle of Man',
    'Israel',
    'Italy',
    'Jamaica',
    'Japan',
    'Jersey',
    'Jordan',
    'Kazakhstan',
    'Kenya',
    'Kiribati',
    'Korea, Democratic People\'s Republic of',
    'Korea, Republic of',
    'Kuwait',
    'Kyrgyzstan',
    'Lao People\'s Democratic Republic',
    'Latvia',
    'Lebanon',
    'Lesotho',
    'Liberia',
    'Libya',
    'Liechtenstein',
    'Lithuania',
    'Luxembourg',
    'Macao',
    'Madagascar',
    'Malawi',
    'Malaysia',
    'Maldives',
    'Mali',
    'Malta',
    'Marshall Islands',
    'Martinique (France)',
    'Mauritania',
    'Mauritius',
    'Mayotte',
    'Mexico',
    'Micronesia, Federated States of',
    'Moldova, Republic of',
    'Monaco',
    'Mongolia',
    'Montenegro',
    'Montserrat',
    'Morocco',
    'Mozambique',
    'Myanmar',
    'Namibia',
    'Nauru',
    'Nepal',
    'Netherlands',
    'Netherlands Antilles',
    'New Caledonia',
    'New Zealand',
    'Nicaragua',
    'Niger',
    'Nigeria',
    'Niue',
    'Norfolk Island',
    'Northern Mariana Islands',
    'North Macedonia, Republic of',
    'Norway',
    'Occupied Palestinian Territory',
    'Oman',
    'Pakistan',
    'Palau',
    'Panama',
    'Papua New Guinea',
    'Paraguay',
    'Peru',
    'Philippines',
    'Pitcairn',
    'Poland',
    'Portugal',
    'Puerto Rico (United States)',
    'Qatar',
    'Réunion',
    'Romania',
    'Russian Federation',
    'Rwanda',
    'Saint Barthélemy (France)',
    'Saint Helena, Ascension and Tristan da Cunha',
    'Saint Kitts and Nevis',
    'Saint Lucia',
    'Saint Martin (France)',
    'Saint Pierre and Miquelon',
    'Saint Vincent and the Grenadines',
    'Samoa',
    'San Marino',
    'São Tomé and Príncipe',
    'Saudi Arabia',
    'Senegal',
    'Serbia',
    'Serbia and Montenegro (until 2006-2009)',
    'Seychelles',
    'Sierra Leone',
    'Singapore',
    'Sint Maarten (Dutch part)',
    'Slovakia',
    'Slovenia',
    'Solomon Islands',
    'Somalia',
    'South Africa',
    'South Georgia and the South Sandwich Islands',
    'South Sudan',
    'Spain',
    'Sri Lanka',
    'Sudan',
    'Suriname',
    'Svalbard and Jan Mayen',
    'Sweden',
    'Switzerland',
    'Syrian Arab Republic',
    'Taiwan, Province of China',
    'Tajikistan',
    'Tanzania, United Republic of',
    'Thailand',
    'Timor-Leste',
    'Togo',
    'Tokelau',
    'Tonga',
    'Trinidad and Tobago',
    'Tunisia',
    'Türkiye',
    'Turkmenistan',
    'Turks and Caicos Islands',
    'Tuvalu',
    'Uganda',
    'Ukraine',
    'United Arab Emirates',
    'United Kingdom',
    'United States',
    'United States Minor Outlying Islands',
    'Uruguay',
    'Uzbekistan',
    'Vanuatu',
    'Venezuela, Bolivarian Republic of',
    'Viet Nam',
    'Virgin Islands, British',
    'Virgin Islands, U.S.',
    'Wallis and Futuna',
    'Western Sahara, non-self-governing territory',
    'Yemen',
    'Zambia',
    'Zimbabwe',
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
 * Setup the document type vocabulary.
 */
function ncms_tags_deploy_setup_years_vocabulary(&$sandbox) {
  $years = range(1980, 2025);
  $not_migrated = [];
  foreach ($years as $key => $year) {
    $year_name = $year;
    $result = ncms_tags_create_and_migrate('year', $year_name, $key, 'field_year');
    if ($result === FALSE) {
      $not_migrated[] = $year_name;
    }
  }
  if (count($not_migrated)) {
    return t('Processed @processed year tags, skipped migration for these tags: @not_migrated', [
      '@processed' => count($years),
      '@not_migrated' => implode(', ', $not_migrated),
    ]);
  }
  else {
    return t('Processed @processed year tags', [
      '@processed' => count($years),
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
