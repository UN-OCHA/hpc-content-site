<?php

namespace Drupal\ncms_ui\Plugin\views\filter;

use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Provides a filter that filters for the latest published version of a node.
 */
#[ViewsFilter("latest_published_version_filter")]
class LatestPublishedVersionFilter extends FilterPluginBase {

  /**
   * Add this filter to the query.
   *
   * Due to the nature of fapi, the value and the operator have an unintended
   * level of indirection. You will find them in $this->operator
   * and $this->value respectively.
   */
  public function query() {
    $node_table_alias = $this->relationship;
    if ($this->query->relationships[$node_table_alias]['base'] != 'node_field_data') {
      return;
    }
    $usage_table_alias = $this->query->relationships[$node_table_alias]['link'];
    if ($this->query->relationships[$usage_table_alias]['base'] != 'entity_usage') {
      return;
    }
    $this->query->addWhere($this->options['group'], '"' . $node_table_alias . '"."vid" = "' . $usage_table_alias . '"."source_vid"', [], 'formula');
  }

}
