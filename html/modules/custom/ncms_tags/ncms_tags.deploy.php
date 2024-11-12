<?php

/**
 * @file
 * Deploy functions for HPC Content Module Tags.
 */

/**
 * Setup the country vocabulary.
 */
function ncms_tags_deploy_1_setup_country_vocabulary(&$sandbox) {
  $countries = [
    // Countries as available in the API.
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
    'Aruba',
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
    'Bolivia',
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
    'Cook Islands',
    'Costa Rica',
    'Côte d\'Ivoire',
    'Croatia',
    'Cuba',
    [
      'name' => ['Curaçao'],
      'alternatives' => ['Curacao'],
    ],
    'Cyprus',
    'Czech Republic',
    'Democratic Republic of the Congo',
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
    [
      'name' => 'Iran (Islamic Republic of)',
      'alternatives' => ['Iran'],
    ],
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
    'Republic of Moldova',
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
    'United Republic of Tanzania',
    'United States',
    'United States Minor Outlying Islands',
    'Uruguay',
    'Uzbekistan',
    'Vanuatu',
    'Venezuela',
    'Viet Nam',
    'Virgin Islands, British',
    'Virgin Islands, U.S.',
    'Wallis and Futuna',
    'Western Sahara, non-self-governing territory',
    'Yemen',
    'Zambia',
    'Zimbabwe',
    // Regions.
    [
      'name' => 'Asia and the Pacific',
      'alternatives' => ['Asia', 'Pacific Islands'],
    ],
    [
      'name' => 'Europe',
      'alternatives' => ['Eastern Europe'],
    ],
    'Latin America and the Caribbean',
    'Middle East and North Africa',
    [
      'name' => 'Southern and Eastern Africa',
      'alternatives' => ['Southern and East Africa', 'Horn of Africa'],
    ],
    'West and Central Africa',
    // World.
    'World',
  ];
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $not_migrated = [];
  foreach ($countries as $key => $country) {
    $country_name = is_array($country) ? $country['name'] : $country;
    $country_alternatives = is_array($country) ? $country['alternatives'] : [];
    $country_term = $tag_migration->createTag('country', $country_name, $key);
    $result = $tag_migration->migrateTag($country_term, $country_alternatives);
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
function ncms_tags_deploy_2_setup_document_type_vocabulary(&$sandbox) {
  $document_types = [
    'HNO',
    'HRP',
    'HNRP',
    'GHO',
    [
      'name' => 'GHO Monthly',
      'alternatives' => ['GHO Monthly update'],
    ],
    [
      'name' => 'Flash Appeal',
      'alternatives' => ['FA'],
    ],
    [
      'name' => 'Regional plan',
      'alternatives' => ['RMRP', 'RRP'],
    ],
    [
      'name' => 'Other plan',
      'alternatives' => ['ERP', 'Rohingya'],
    ],
  ];
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $not_migrated = [];
  foreach ($document_types as $key => $document_type) {
    $document_type_name = is_array($document_type) ? $document_type['name'] : $document_type;
    $document_type_alternatives = is_array($document_type) ? $document_type['alternatives'] : [];
    $document_type_term = $tag_migration->createTag('document_type', $document_type_name, $key);
    $result = $tag_migration->migrateTag($document_type_term, $document_type_alternatives);
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
function ncms_tags_deploy_3_setup_years_vocabulary(&$sandbox) {
  $years = array_reverse(range(1980, 2025));
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $not_migrated = [];
  foreach ($years as $key => $year) {
    $year_term = $tag_migration->createTag('year', $year, $key);
    $result = $tag_migration->migrateTag($year_term);
    if ($result === FALSE) {
      $not_migrated[] = $year;
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
 * Setup the document type vocabulary.
 */
function ncms_tags_deploy_4_setup_months_vocabulary(&$sandbox) {
  $months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
  ];
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $not_migrated = [];
  foreach ($months as $key => $month) {
    $month_term = $tag_migration->createTag('month', $month, $key);
    $result = $tag_migration->migrateTag($month_term);
    if ($result === FALSE) {
      $not_migrated[] = $month;
    }
  }
  if (count($not_migrated)) {
    return t('Processed @processed month tags, skipped migration for these tags: @not_migrated', [
      '@processed' => count($months),
      '@not_migrated' => implode(', ', $not_migrated),
    ]);
  }
  else {
    return t('Processed @processed year tags', [
      '@processed' => count($months),
    ]);
  }
}

/**
 * Setup the theme vocabulary.
 */
function ncms_tags_deploy_5_setup_theme_vocabulary(&$sandbox) {
  $themes = [
    'Access' => [
      'Bureaucratic impediments',
      'Safe passage',
      'Operational constraints',
      'Cross-border operation',
      'Security Council authorization',
      'Safe zones',
    ],
    'Accountability to affected people' => [
      'Complaint mechanisms',
      'Transparency',
      'Monitoring',
      'Program monitoring',
      'Community consultations',
    ],
    'Anticipatory action' => [
      'alternatives' => ['Anticipatory Action'],
      'children' => [
        'Preparedness',
        'Early warning systems',
        'Risk mitigation',
        'Disaster preparedness',
        'Forecast-based financing',
      ],
    ],
    'Camp coordination and camp management (CCCM)' => [
      'Site planning and development',
      'Camp infrastructure maintenance',
      'Community mobilization',
      'Protection and safety in camps',
      'Shelter allocation',
      'Displacement tracking and monitoring',
    ],
    'Cash' => [
      'Cash assistance',
      'Vouchers',
      'Economic recovery',
      'Livelihood support',
      'Cash for work',
      'Cash transfers',
    ],
    'Children' => [
      'Child protection',
      'Education in crisis',
    ],
    'Climate change' => [
      'Climate emergencies',
      'Displacement',
      'Natural disasters',
      'Adaptation strategies',
      'Environmental degradation',
    ],
    'Conflict' => [
      'Civilian casualties',
      'Peacebuilding',
    ],
    'Coordination' => [
      'Interagency',
      'Joint operations',
      'Response planning',
      'Resource sharing',
      'Field-level coordination',
      'Cluster coordination',
    ],
    'COVID-19' => [
      'Vaccination campaigns',
      'Health services',
      'Disease outbreaks',
      'Social distancing measures',
      'Pandemic response',
      'Vaccine shortages',
    ],
    'Data and technology' => [
      'alternatives' => ['Data Responsibility'],
      'children' => [],
    ],
    'Disasters' => [
      'alternatives' => ['Disaster Response', 'Natural Disasters'],
      'children' => [
        'Natural disasters',
        'Cyclones',
        'Earthquakes',
        'Flooding',
        'Natural disaster response',
        'Relief',
      ],
    ],
    'Disease' => [
      'Cholera',
      'Malaria',
      'Measles',
      'COVID-19',
      'Outbreaks',
      'Malaria',
    ],
    'Displacement' => [
      'alternatives' => ['Migrants', 'Refugees'],
      'children' => [
        'Internal displacement',
        'Refugee movements',
        'IDP camps',
        'Cross-border displacement',
        'Refugee flows',
      ],
    ],
    'Early recovery' => [
      'Site planning and development',
      'Camp infrastructure maintenance',
      'Community mobilization',
      'Protection and safety in camps',
      'Shelter allocation',
      'Displacement tracking and monitoring',
    ],
    'Economy' => [
      'Economic shocks',
      'Poverty',
      'Job loss',
      'Livelihoods',
      'Economic sensitivity',
      'Deflation',
      'Economic instability',
    ],
    'Education' => [
      'Schools in emergencies',
      'Remote learning',
      'Access to education',
      'Emergency education programs',
      'School rebuilding',
    ],
    'Emergency shelter' => [
      'Shelter materials distribution',
      'Transitional shelter solutions',
      'Temporary housing setup',
      'Site selection for temporary shelters',
    ],
    'Emergency telecommunications' => [
      'Satellite communications',
      'Radio communications',
      'Mobile network services',
      'Internet connectivity',
      'Telecommunication infrastructure',
      'Network security',
    ],
    'Financing' => [
      'Funding',
      'Funding gaps',
      'Donor contributions',
      'Pooled funds',
      'Contributions',
      'Donor shortfalls',
    ],
    'Food security' => [
      'alternatives' => ['Food Insecurity'],
      'children' => [
        'Acute food insecurity',
        'Malnutrition',
        'Cash assistance',
        'Famine prevention',
        'Agricultural assistance',
      ],
    ],
    'Funding' => [
      'Humanitarian funding',
      'Funding gaps',
      'Donor contributions',
      'Resource mobilization',
      'Funding appeals',
    ],
    'Gender' => [
      'Gender equality',
      'Women empowerment',
      'Gender-based violence (GBV)',
      'Reproductive health',
      'Gender-sensitive programming',
      "Women's participation in humanitarian work",
    ],
    'Gender-based violence (GBV)' => [
      'alternatives' => ['Gender-Based Violence'],
      'children' => [
        'Survivor support',
        'Women protection',
        'Legal aid',
        'Sexual violence prevention',
        'Survivor services',
        'Safe spaces for women',
      ],
    ],
    'Health' => [
      'Emergency health services',
      'Vaccinations',
      'Healthcare access',
      'Mental health support',
      'Trauma care',
      'Emergency health response',
    ],
    'Humanitarian-development-peace collaboration' => [
      'alternatives' => ['Development', 'Nexus', 'Peacebuilding'],
      'children' => [
        'Resilience building',
        'Sustainable development',
        'Partnerships',
        'Capacity building',
        'Resilience building',
        'Development linkages',
      ],
    ],
    'Hunger' => [
      'alternatives' => ['Famine'],
      'children' => [
        'Acute food insecurity',
        'Malnutrition',
        'Food aid',
        'Agriculture support',
        'Food aid',
        'Emergency feeding',
      ],
    ],
    'Immunizations' => [
      'alternatives' => ['Vaccines'],
      'children' => [
        'COVID-19 vaccines',
        'Measles vaccination',
        'Routine immunizations',
        'Routine vaccine programs',
        'Cold chain support',
      ],
    ],
    'Inter-agency' => [
      'alternatives' => ['Interagency'],
      'children' => [
        'Coordination',
        'Partnerships',
        'Joint planning',
        'Resource sharing',
        'Humanitarian coordination',
        'Cluster approach',
      ],
    ],
    'Localization' => [
      'Local partnerships',
      'Community engagement',
      'National actors',
      'Capacity building',
      'Support to local NGOs',
      'Capacity strengthening',
    ],
    'Logistics' => [
      'Transportation of aid supplies',
      'Warehousing and storage',
      'Supply chain management',
      'Coordination of humanitarian convoys',
      'Customs and border clearance',
      'Air, land, and sea operations',
    ],
    'Mental health and psychosocial support' => [
      'alternatives' => ['Mental Health', 'Psychosocial Support'],
      'children' => [
        'Psychological first aid',
        'Trauma recovery',
        'Community-based support',
        'Access to mental health services',
        'Counseling services',
        'Trauma recovery',
      ],
    ],
    'Needs analysis' => [
      'alternatives' => ['Analysis', 'Needs'],
      'children' => [
        'Data collection',
        'Joint needs assessment',
        'Vulnerability analysis',
        'Humanitarian planning',
        'Vulnerability assessments',
        'Data-driven response',
      ],
    ],
    'Negotiation' => [
      'Humanitarian diplomacy',
      'Access negotiations',
      'Peace talks',
      'Ceasefire agreements',
      'Access agreements',
      'Local negotiations',
    ],
    'Nutrition' => [
      'Malnutrition',
      'Food aid',
      'Child nutrition',
      'Emergency feeding',
      'Child malnutrition',
      'Supplementary feeding',
    ],
    'Partners' => [
      'alternatives' => ['NGO', 'Private Sector', 'UNDAC'],
      'children' => [],
    ],
    'Pooled funds' => [
      'alternatives' => ['CBPFs', 'CERF'],
      'children' => [
        'Country-Based Pooled Funds',
        'Central Emergency Response Fund',
        'Flexible funding',
        'Donor coordination',
        'Emergency allocations',
        'CERF funding',
      ],
    ],
    'Poverty' => [
      'Economic inequality',
      'Job loss',
      'Social safety nets',
      'Cash assistance',
      'Livelihood recovery',
      'Income support',
    ],
    'Protection' => [
      'Child protection',
      'Gender-based violence (GBV)',
      'Legal aid',
      'Safe spaces',
      'Legal aid',
      'Protection of civilians',
      'Mine action',
      'Housing, land, and property',
    ],
    'Protection from sexual exploitation and abuse' => [
      'alternatives' => ['Sexual Abuse'],
      'children' => [
        'Legal frameworks',
        'Survivor assistance',
        'Prevention programs',
        'Reporting mechanisms',
        'Safeguarding programs',
        'Incident reporting',
      ],
    ],
    'Violence' => [
      'Armed conflict',
      'Civilian casualties',
      'Gender-based violence (GBV)',
      'Child protection',
      'Armed violence',
      'Civilian protection',
    ],
    'Vulnerable areas' => [
      'Conflict zones',
      'Drought-affected areas',
      'Urban slums',
      'Disaster-prone regions',
      'Conflict zones',
    ],
    'Water, Sanitation, and Hygiene (WASH)' => [
      'Water supply systems',
      'Sanitation facility construction',
      'Hygiene promotion and education',
      'Waste management and disposal',
      'Water quality monitoring',
      'Latrine construction and maintenance',
    ],
    'Women' => [
      'alternatives' => ['Women and Girls'],
      'children' => [
        'Women empowerment',
        'Gender equality',
        'Protection services',
        'Reproductive health',
        "Women's rights",
        'Economic participation',
      ],
    ],
  ];
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $not_migrated = [];
  foreach (array_keys($themes) as $parent_key => $theme) {
    $parent_term = $tag_migration->createTag('theme', $theme, $parent_key);
    $result = $tag_migration->migrateTag($parent_term, !empty($themes[$theme]['alternatives']) ? $themes[$theme]['alternatives'] : []);
    if ($result === FALSE) {
      $not_migrated[] = $theme;
    }
    $children = array_key_exists('children', $themes[$theme]) ? $themes[$theme]['children'] : $themes[$theme];
    foreach ($children as $child_key => $child_tag) {
      $tag_migration->createTag('theme', $child_tag, $child_key, $parent_term);
    }
  }
  if (count($not_migrated)) {
    return t('Processed @processed theme tags, skipped migration for these tags: @not_migrated', [
      '@processed' => count($themes),
      '@not_migrated' => implode(', ', $not_migrated),
    ]);
  }
  else {
    return t('Processed @processed theme tags', [
      '@processed' => count($themes),
    ]);
  }
}

/**
 * Migrate some special tags into the new taxonomies.
 */
function ncms_tags_deploy_6_migrate_special_terms(&$sandbox) {
  /** @var \Drupal\ncms_tags\TagMigration $tag_migration */
  $tag_migration = \Drupal::service('ncms_tags.tag_migration');
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

  $gho_tags = [
    'GHO 2022' => [
      'document_type' => 'GHO',
      'year' => '2022',
    ],
    'GHO 2023' => [
      'document_type' => 'GHO',
      'year' => '2023',
    ],
    'GHO 2024' => [
      'document_type' => 'GHO',
      'year' => '2024',
    ],
  ];
  foreach ($gho_tags as $tag => $fields) {
    /** @var \Drupal\taxonomy\TermInterface $tag_term */
    $source_tags = $term_storage->loadByProperties([
      'vid' => 'major_tags',
      'name' => $tag,
    ]);
    $target_terms = [];
    foreach ($fields as $vid => $value) {
      $target_terms[] = $tag_migration->createTag($vid, $value);
    }
    $tag_migration->migrateTermReferences($source_tags, $target_terms);
    foreach ($source_tags as $tag_term) {
      $tag_term->delete();
    }
  }
}

/**
 * Delete some obsolete tags.
 */
function ncms_tags_deploy_7_delete_obsolete_tags(&$sandbox) {
  $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $obsolete_tags = [
    'Achievements',
    'Dashboard',
    'Delivering Better',
    'Flash Appeal Template',
    'Foreword',
    'Global Trends',
    'HNO template',
    'Humanitarian Action',
    'Introduction',
    'Methodology',
    'Regional',
    'Regional Overview',
    'Response Plans',
    'Section 1: Global Trends',
    'Section 2: Response Plans',
    'Section 3: Delivering Better',
    'Snapshot',
    'Standalone',
  ];
  foreach ($obsolete_tags as $tag) {
    $tag_terms = $term_storage->loadByProperties([
      'vid' => 'major_tags',
      'name' => $tag,
    ]);
    foreach ($tag_terms as $tag_term) {
      $tag_term->delete();
    }
  }
}
