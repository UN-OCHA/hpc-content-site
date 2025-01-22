<?php

namespace Drupal\gho_layouts\Plugin\Layout;

/**
 * Configurable four column layout plugin class.
 *
 * @internal
 *   Plugin classes are internal.
 */
class FourColumnInteractiveContentLayout extends MultiColumnLayoutBase {

  /**
   * {@inheritdoc}
   */
  protected function getWidthOptions() {
    return [
      '25-25-25-25' => '25%/25%/25%/25%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultWidth() {
    return '25-25-25-25';
  }

}
