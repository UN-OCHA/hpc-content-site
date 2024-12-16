<?php

namespace Drupal\ncms_paragraphs\Entity;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Base class for NCMS paragraphs.
 */
abstract class NcmsParagraphBase extends Paragraph implements NcmsParagraphInterface {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $form_object */
    $form_object = $form_state->getFormObject();
    // Move the behavior plugin from a stand-alone fieldset into existing field
    // groups.
    if (!empty($form['#fieldgroups']) && !empty($form['behavior_plugins'])) {
      $form['behavior_plugins']['#parents'] = ['behavior_plugins'];
      $form['behavior_plugins'] = $form_object->behaviorPluginsForm($form['behavior_plugins'], $form_state, $form);
      unset($form['behavior_plugins']['#process']);
      $group_name = 'group_settings';
      if (empty($form['#fieldgroups'][$group_name])) {
        $group = clone end($form['#fieldgroups']);
        $group->children = ['behavior_plugins'];
        $group->format_settings['formatter'] = 'close';
        $group->weight = $group->weight + 1;
        $group->group_name = $group_name;
        $form['#fieldgroups'][$group->group_name] = $group;
      }
      else {
        $form['#fieldgroups'][$group_name]->children[] = 'behavior_plugins';
      }
      $form['#fieldgroups'][$group_name]->label = $this->t('Additional settings');
      $form['#group_children']['behavior_plugins'] = $group_name;
    }

    // Don't trigger "Required" messages when canceling the add/edit form.
    $form['actions']['cancel']['#limit_validation_errors'] = [];

    // Add our library to improve the display.
    $form['#attached']['library'][] = 'ncms_paragraphs/paragraph_edit_form';

    if (!empty($form['field_dataset'])) {
      // Hide the fieldset.
      $form['field_dataset']['widget'][0]['#type'] = 'container';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {}

  /**
   * {@inheritdoc}
   */
  public function isFullWidth() {
    if (!$this->hasField('field_full_width')) {
      return FALSE;
    }
    return $this->get('field_full_width')->value;
  }

}
