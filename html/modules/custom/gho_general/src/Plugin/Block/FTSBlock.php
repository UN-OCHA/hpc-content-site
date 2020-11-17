<?php

namespace Drupal\gho_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays FTS logo and links to site.
 *
 * @Block(
 *   id = "soft_footer_fts",
 *   admin_label = @Translation("FTS"),
 *   category = @Translation("Soft Footer"),
 * )
 */
class FTSBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => $this->t('Financial Tracking Service'),
    ];
  }

}
