<?php

namespace Drupal\gho_general\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to replace non-breaking and consecutive spaces.
 *
 * @Filter(
 *   id = "filter_space_corrector",
 *   title = @Translation("Replace non-breaking and consecutive spaces"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 10
 * )
 */
class FilterSpaceCorrector extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $text = preg_replace(['/&nbsp;/', '/\xc2\xa0/', '/ {2,}/'], ' ', $text);
    return new FilterProcessResult($text);
  }

}
