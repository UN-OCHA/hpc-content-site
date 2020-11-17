<?php

namespace Drupal\gho_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays FTS logo and links to site.
 *
 * @Block(
 *   id = "soft_footer_huminsight",
 *   admin_label = @Translation("HumInsight"),
 *   category = @Translation("Soft Footer"),
 * )
 */
class HumInsightBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => $this->t('Humanitarian InSight'),
    ];
  }

}
