<?php

namespace Drupal\ncms_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsInterface;
use Drupal\Core\Asset\AttachedAssets;

/**
 * Defines a "reload page" command for ajax responses.
 */
class ReloadPageCommand implements CommandInterface, CommandWithAttachedAssetsInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'reloadPage',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAttachedAssets() {
    $assets = new AttachedAssets();
    $assets->setLibraries(['ncms_ui/ajax_commands']);
    return $assets;
  }

}
