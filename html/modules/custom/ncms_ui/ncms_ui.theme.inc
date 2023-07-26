<?php

/**
 * @file
 * Theme hooks and general purpose theme related functions.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;

/**
 * Preprocess tooltip.
 */
function ncms_ui_preprocess_tooltip(&$variables) {
  $trigger_id = Html::getUniqueId('tooltip-trigger');
  $id = Html::getUniqueId('tooltip');
  $variables['trigger_id'] = $trigger_id;
  $variables['id'] = $id;
  $options = Json::encode([
    'block' => $variables['id'],
    'placement' => 'top',
  ]);
  $attributes = new Attribute();
  $attributes['id'] = $trigger_id;
  $attributes['data-tooltip'] = $options;
  $attributes['class'] = [
    'icon',
    $variables['icon'],
  ];
  $variables['attributes'] = $attributes;
}
