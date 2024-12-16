<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;

/**
 * Entity class for paragraphs of type interactive_content_2_columns.
 */
class InteractiveContent2Columns extends NcmsParagraphBase {

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(&$form, FormStateInterface $form_state) {
    parent::entityFormAlter($form, $form_state);

    /** @var \Drupal\layout_paragraphs\Form\EditComponentForm $form_object */
    $form_object = $form_state->getFormObject();

    // Move the behavior plugin from a stand-alone fieldset into existing field
    // groups.
    if (!empty($form['#fieldgroups']) && !empty($form['layout_paragraphs'])) {

      // First, build out the full layout paragraphs subform, so that we have
      // it available. Coming in this has only a process property and would be
      // built later.
      $form['layout_paragraphs']['#parents'] = ['layout_paragraphs'];
      $subform_state = SubformState::createForSubform($form['layout_paragraphs'], $form, $form_state);
      $form['layout_paragraphs'] = $form_object->layoutParagraphsBehaviorForm($form['layout_paragraphs'], $subform_state, $form);

      // Having the type be a container is important, otherwise field group
      // refuses to put this into groups.
      $form['layout_paragraphs']['#type'] = 'container';

      $input = $form_state->getUserInput();
      if (empty($input['_triggering_element_name'])) {
        // Somehow we need to unset the process here on the first rendering of
        // the form, otherwise the grouping into field groups, as defined via
        // the Field UI in the drupal backend, does not work. Processing has
        // already happened in layoutParagraphsBehaviorForm() above, so that
        // should not be an issue per-se.
        // But on the other hand, we cannot unset the process unconditionally,
        // otherwise the form validation fails when switching layouts. See
        // MultiColumnLayoutBase::buildConfigurationForm for the additional
        // modifcations to the user input array. Unsetting the process array
        // here, somehow leads to the changes in
        // MultiColumnLayoutBase::buildConfigurationForm not being transmitted
        // up-chain and would eventually lead to a validation error triggered
        // in FormValidator::performRequiredValidation.
        unset($form['layout_paragraphs']['#process']);
      }
      $group_name = 'group_layout';
      if (empty($form['#fieldgroups'][$group_name])) {
        $group = clone end($form['#fieldgroups']);
        $group->children = ['layout_paragraphs'];
        $group->format_settings['formatter'] = 'close';
        $group->weight = $group->weight + 1;
        $group->group_name = $group_name;
        $form['#fieldgroups'][$group->group_name] = $group;
      }
      else {
        $form['#fieldgroups'][$group_name]->children[] = 'layout_paragraphs';
      }
      $form['#fieldgroups'][$group_name]->label = $this->t('Layout');
      $form['#group_children']['layout_paragraphs'] = $group_name;
    }

  }

}
