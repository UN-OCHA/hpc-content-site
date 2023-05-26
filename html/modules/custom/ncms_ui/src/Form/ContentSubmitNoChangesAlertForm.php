<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form class for an alert form if no changes have been made.
 */
class ContentSubmitNoChangesAlertForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_submit_no_changes_alert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('No changes have been made to the already published version. Please make some changes before publishing again.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Ok');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $triggering_element = NULL, $entity = NULL) {
    $form = parent::buildForm($form, $form_state);

    $form['description'] = ['#markup' => $this->getQuestion()];
    $form['actions']['submit']['#access'] = FALSE;
    // This is a special class to which JavaScript assigns dialog closing
    // behavior.
    $form['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';
    $form['actions']['cancel']['#title'] = $this->getConfirmText();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty stub just because FormInterface requires it.
  }

}
