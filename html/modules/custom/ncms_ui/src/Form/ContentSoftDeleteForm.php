<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\Ajax\ReloadPageCommand;
use Drupal\ncms_ui\Entity\ContentInterface;

/**
 * Form class for content submit confirm forms.
 */
class ContentSoftDeleteForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_soft_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->getNodeFromRouteMatch();
    return $this->t('This will remove this @type, including already published versions, from public display anywhere. Are you sure?', [
      '@type' => strtolower($entity->type->entity->label()),
    ]);
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
      $entity = $this->getNodeFromRouteMatch();
    }

    $form['description'] = ['#markup' => $this->getQuestion()];
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'ajaxCallbackConfirm'],
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
      // Submit the form.
      $this->submitForm($form, $form_state, TRUE);

      // Reload the page.
      $response->addCommand(new ReloadPageCommand());
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
  public function submitForm(array &$form, FormStateInterface $form_state, $submit_in_ajax_context = FALSE) {
    if ($this->requestStack->getCurrentRequest()->isXmlHttpRequest() && !$submit_in_ajax_context) {
      // If this submission is done via ajax, we first need to do the
      // confirmation.
      return;
    }

    // Confirmed, so we mark the node as deleted.
    $entity = $this->getNodeFromRouteMatch();
    $entity->setDeleted();
    $entity->save();

    // And inform the user.
    $this->messenger()->addStatus($this->t('@type %title has been moved to the trash bin.', [
      '@type' => $entity->type->entity->label(),
      '%title' => $entity->label(),
    ]));

    $form_state->setRedirectUrl($entity->getOverviewUrl());
  }

  /**
   * Get the current entity from the route match.
   *
   * @return \Drupal\ncms_ui\Entity\ContentInterface|null
   *   The entity or NULL.
   */
  private function getNodeFromRouteMatch() {
    $entity = $this->getRouteMatch()->getParameter('node');
    return $entity && $entity instanceof ContentInterface ? $entity : NULL;
  }

}
