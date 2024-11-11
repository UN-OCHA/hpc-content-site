<?php

namespace Drupal\ncms_tags;

/**
 * Class for helping with common taxonomies.
 */
class CommonTaxonomyService {

  /**
   * Get a map to relate taxonomy bundle and field name.
   *
   * @return array
   *   Keys are the taxonomy vids, values the field names.
   */
  public function getCommonTaxonomyBundleFieldMap() {
    return [
      'document_type' => 'field_document_type',
      'country' => 'field_country',
      'year' => 'field_year',
      'month' => 'field_month',
      'theme' => 'field_theme',
    ];
  }

  /**
   * Get the field name for the given taxonomy bundle.
   *
   * @param string $bundle
   *   The bundle name, that's the vid of the taxonomy.
   *
   * @return string|null
   *   The field name for the given bundle.
   *
   * @todo This should probably throw an expception instead of returning NULL.
   */
  public function getFieldNameForTaxonomyBundle($bundle) {
    $map = $this->getCommonTaxonomyBundleFieldMap();
    return $map[$bundle] ?? NULL;
  }

  /**
   * Get an array of field names for the common taxonomies.
   *
   * @return string[]
   *   An array of field names.
   */
  public function getCommonTaxonomyFieldNames() {
    return array_values($this->getCommonTaxonomyBundleFieldMap());
  }

  /**
   * Get an array of bundle names for the common taxonomies.
   *
   * @return string[]
   *   An array of taxonomy vids.
   */
  public function getCommonTaxonomyBundles() {
    return array_keys($this->getCommonTaxonomyBundleFieldMap());
  }

}
