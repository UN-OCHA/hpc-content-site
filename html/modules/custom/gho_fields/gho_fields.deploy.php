<?php

/**
 * @file
 * Post update functions for GHO Fields.
 */

use Drupal\Core\Field\FieldItemInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

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
    $paragraph->setSyncing(TRUE);
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
    $paragraph->setSyncing(TRUE);
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
      $translated_paragraph->setSyncing(TRUE);
      $translated_paragraph->save();
    }
  }
}

/**
 * Migrate bottom figure row paragraph types to the new paragraph types.
 */
function gho_fields_deploy_migrate_bottom_figure_rows_paragraphs_to_top_figures() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('paragraph');

  /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
  $paragraphs = $storage->loadByProperties([
    'type' => ['bottom_figure_row'],
  ]);
  foreach ($paragraphs as $paragraph) {
    if (!$paragraph->hasField('field_figures') || $paragraph->get('field_figures')->isEmpty()) {
      $paragraph->delete();
      continue;
    }

    $view_mode = $paragraph->getBehaviorSetting('paragraphs_viewmode_behavior', 'view_mode');
    $target_bundle = $view_mode == 'top_figures' ? 'top_figures' : 'top_figures_small';

    $field_config = $entity_type_manager
      ->getStorage('field_config')
      ->load($paragraph->getEntityTypeId() . '.' . $target_bundle . '.field_figures');
    $max_figures = $field_config->getThirdPartySetting('field_config_cardinality', 'cardinality_config');
    foreach ($paragraph->getTranslationLanguages() as $language) {
      if ($language->isDefault()) {
        continue;
      }
      if (!$paragraph->hasTranslation($language->getId())) {
        continue;
      }

      $translated_paragraph = $paragraph->getTranslation($language->getId());
      $figures = $translated_paragraph->get('field_figures');
      $figures->filter(function (FieldItemInterface $figure) use ($language) {
        return $figure->getLangcode() == $language->getId();
      });

      if (!$figures || $figures->isEmpty()) {
        $translated_paragraph->delete();
        continue;
      }

      if ($figures->count() > $max_figures) {
        continue;
      }

      /** @var \Drupal\paragraphs\ParagraphInterface $top_figures_paragraph */
      $top_figures_paragraph = ghi_fields_create_top_figures_from_paragraph($translated_paragraph, $target_bundle);
      if ($top_figures_paragraph) {
        $translated_paragraph->delete();
      }
    }

    $figures = $paragraph->get('field_figures');
    $figures->filter(function (FieldItemInterface $figure) use ($paragraph) {
      return $figure->getLangcode() == $paragraph->language()->getId();
    });
    if ($figures->count() > $max_figures) {
      continue;
    }

    $top_figures_paragraph = ghi_fields_create_top_figures_from_paragraph($paragraph, $target_bundle);
    if ($top_figures_paragraph) {
      $paragraph->delete();
    }
  }
}

/**
 * Create a top figures paragraph from a bottom_figure_row paragraph.
 *
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The original bottom_figure_row paragraph.
 * @param string $bundle
 *   The new paragraph type, either top_figures or top_figures_small.
 *
 * @return \Drupal\paragraphs\ParagraphInterface
 *   The newly created paragraph.
 */
function ghi_fields_create_top_figures_from_paragraph(ParagraphInterface $paragraph, $bundle) {
  if ($paragraph->bundle() != 'bottom_figure_row') {
    return NULL;
  }
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('paragraph');
  /** @var \Drupal\paragraphs\ParagraphInterface $top_figures_paragraph */
  $top_figures_paragraph = $storage->create([
    'type' => $bundle,
    'status' => $paragraph->isPublished(),
    'parent_id' => $paragraph->get('parent_id')->value,
    'parent_type' => $paragraph->get('parent_type')->value,
    'parent_field_name' => $paragraph->get('parent_field_name')->value,
    'field_figures' => $paragraph->get('field_figures')->getValue(),
    'field_dataset' => $paragraph->get('field_dataset')->getValue(),
  ]);
  $top_figures_paragraph->setBehaviorSettings('promoted_behavior', $paragraph->getAllBehaviorSettings()['promoted_behavior'] ?? []);
  $top_figures_paragraph->setBehaviorSettings('ncms_paragraphs', [
    'replaces' => $paragraph->uuid(),
  ]);
  $top_figures_paragraph->setSyncing(TRUE);
  $top_figures_paragraph->save();

  $parent_field_name = $paragraph->get('parent_field_name')->value;
  $parent = $paragraph->getParentEntity();
  $values = $parent->get($parent_field_name)->getValue();

  foreach ($values as &$item) {
    if ($item['target_id'] != $paragraph->id()) {
      continue;
    }
    $item['target_id'] = $top_figures_paragraph->id();
    $item['target_revision_id'] = $top_figures_paragraph->getRevisionId();
  }
  $parent->get($parent_field_name)->setValue($values);
  $parent->setNewRevision(FALSE);
  $parent->setSyncing(TRUE);
  $parent->save();

  return $top_figures_paragraph;
}

/**
 * Migrate bottom figure row paragraph types to the new paragraph types.
 */
function gho_fields_deploy_force_updates() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('paragraph');

  /** @var \Drupal\paragraphs\ParagraphInterface[] $paragraphs */
  $paragraphs = $storage->loadByProperties([
    'type' => ['top_figures', 'top_figures_small'],
  ]);
  $node_ids = [];
  foreach ($paragraphs as $paragraph) {
    $parent = $paragraph->getParentEntity();
    if (!$parent instanceof NodeInterface || in_array($parent->id(), $node_ids)) {
      continue;
    }
    $node_ids[] = $parent->id();
    $parent->set('force_update', \Drupal::time()->getRequestTime());
    $parent->setNewRevision(FALSE);
    $parent->setSyncing(TRUE);
    $parent->save();
  }
}
