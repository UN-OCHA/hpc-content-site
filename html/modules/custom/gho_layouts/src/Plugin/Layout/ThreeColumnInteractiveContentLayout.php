<?php

namespace Drupal\gho_layouts\Plugin\Layout;

/**
 * Configurable three column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class ThreeColumnInteractiveContentLayout extends MultiColumnLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '33-34-33' => '33%/34%/33%',
      '50-25-25' => '50%/25%/25%',
      '25-50-25' => '25%/50%/25%',
      '25-25-50' => '25%/25%/50%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '33-34-33';
  }

}
