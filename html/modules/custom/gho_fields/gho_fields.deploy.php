<?php

/**
 * @file
 * Post update functions for GHO Fields.
 */

/**
 * Copy data from old fields to new fields.
 */
function gho_fields_deploy_post_rename_datawrapper_fields(&$sandbox) {
  /** @var \Drupal\paragraphs\Entity\Paragraph[] $paragraphs */
  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
    'type' => ['interactive_content'],
  ]);
  foreach ($paragraphs as $paragraph) {
    if (!$paragraph->hasField('field_show_datawrapper')) {
      continue;
    }
    if (!$paragraph->hasField('field_show_interactive_content') || !$paragraph->get('field_show_interactive_content')->isEmpty()) {
      continue;
    }
    $paragraph->get('field_show_interactive_content')->setValue([
      'value' => $paragraph->get('field_show_datawrapper')->value,
    ]);
    $paragraph->isSyncing();
    $paragraph->save();
  }
}

/**
 * Migrate bottom figure row data from doublefield to custom field.
 */
function gho_fields_deploy_migrate_bottom_figure_rows() {
  /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
  $paragraphs = \Drupal::entityTypeManager()->getStorage('paragraph')->loadByProperties([
    'type' => ['bottom_figure_row'],
  ]);
  foreach ($paragraphs as $paragraph) {
    if (!$paragraph->hasField('field_figures') || !$paragraph->get('field_figures')->isEmpty()) {
      continue;
    }
    if (!$paragraph->hasField('field_bottom_figures') || $paragraph->get('field_bottom_figures')->isEmpty()) {
      continue;
    }
    $figures = $paragraph->get('field_bottom_figures');
    if (empty($figures)) {
      continue;
    }
    foreach ($figures as $figure) {
      $paragraph->get('field_figures')->appendItem([
        'label' => $figure->first,
        'value' => $figure->second,
      ]);
    }
    $paragraph->isSyncing();
    $paragraph->save();

    foreach ($paragraph->getTranslationLanguages() as $language) {
      if ($language->isDefault()) {
        continue;
      }
      if (!$paragraph->hasTranslation($language->getId())) {
        continue;
      }
      $translated_paragraph = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph, $language->getId());
      $figures = $translated_paragraph->get('field_bottom_figures');
      if (!$figures || $figures->isEmpty()) {
        continue;
      }
      foreach ($figures as $figure) {
        $translated_paragraph->get('field_figures')->appendItem([
          'label' => $figure->first,
          'value' => $figure->second,
        ]);
      }
      $translated_paragraph->isSyncing();
      $translated_paragraph->save();
    }
  }
}
