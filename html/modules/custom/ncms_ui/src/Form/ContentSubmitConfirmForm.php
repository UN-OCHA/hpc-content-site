<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form class for content submit confirm forms.
 */
class ContentSubmitConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_submit_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure?');
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

    if ($entity === NULL) {
      $entity = $this->getRouteMatch()->getParameter('node');
    }

    if ($triggering_element) {
      $confirm_question = $triggering_element['#ajax']['confirm_question'];
      $confirm_field = $triggering_element['#ajax']['confirm_field'];
      $form['confirm_field'] = [
        '#type' => 'hidden',
        '#value' => $confirm_field,
      ];
      $form['submit_button'] = [
        '#type' => 'hidden',
        '#value' => (string) $triggering_element['#attributes']['data-drupal-selector'],
      ];
    }

    $form['description'] = ['#markup' => $confirm_question];
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'ajaxCallbackConfirm'],
      'url' => $entity && !$entity->isNew() ? Url::fromRoute('entity.node_edit.submit_confirm', [
        'node' => $entity->id(),
      ]) : Url::fromRoute('entity.node_add.submit_confirm'),
      'options' => [
        'query' => [
          FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
        ],
      ],
    ];
    // This is a special class to which JavaScript assigns dialog closing
    // behavior.
    $form['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';
    return $form;
  }

  /**
   * Ajax callback for the confirm button.
   */
  public function ajaxCallbackConfirm(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    if ((string) $form_state->getValue('op') == (string) $this->getConfirmText()) {
      $input = $form_state->getUserInput();
      $confirm_field = $input['confirm_field'];
      $submit_button = $input['submit_button'];
      // Remove any is_changed class just in case to prevent the beforeunload
      // event set up by layout paragraphs to trigger a browser warning. See
      // https://git.drupalcode.org/project/layout_paragraphs/-/blame/2.0.x/js/builder.js#L182
      $response->addCommand(new InvokeCommand('.is_changed', 'removeClass', ['is_changed']));
      // Mark as confirmed and submit the original form.
      $response->addCommand(new InvokeCommand('input[name="' . $confirm_field . '"]', 'val', [1]));
      $response->addCommand(new InvokeCommand('input[data-drupal-selector="' . $submit_button . '"]', 'mousedown'));
    }

    return $response;
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
