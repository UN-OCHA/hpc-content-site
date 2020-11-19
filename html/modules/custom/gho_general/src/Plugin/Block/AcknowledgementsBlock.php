<?php

namespace Drupal\gho_general\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block that displays Acknowledgements.
 *
 * @Block(
 *   id = "soft_footer_acknowledgements",
 *   admin_label = @Translation("GHO Acknowledgements"),
 *   category = @Translation("Soft Footer"),
 * )
 */
class AcknowledgementsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#title' => $this->t('GHO Acknowledgements'),
    ];
  }

}
