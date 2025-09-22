<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\ncms_ui\Ajax\ReloadPageCommand;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\Media\MediaBase;
use Drupal\ncms_ui\Entity\MediaInterface;
use Drupal\ncms_ui\Traits\RouteMatchEntityTrait;
use Drupal\node\NodeInterface;

/**
 * Form class for content submit confirm forms.
 */
class ContentSoftDeleteForm extends ConfirmFormBase {

  use RouteMatchEntityTrait;

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
    $entity = $this->getEntityFromRouteMatch();
    $t_args = [
      '@type' => strtolower($entity->getBundleLabel()),
    ];
    if ($entity instanceof MediaInterface) {
      if ($entity->hasMandatoryReferences()) {
        $t_args['@places_used_url'] = $entity->toUrl('places-used')->toString();
        return $this->t('This @type is currently referred to in article paragraphs where it is mandatory. It cannot be moved to trash until those references are removed manually. Please refer to the <a href="@places_used_url" target="_blank">Places used</a> list.', $t_args);
      }
      elseif ($entity->hasOptionalReferences()) {
        $usage_summary = $this->getUsageSummary($entity);
        $t_args['@usage_summary'] = Markup::create(implode('', $usage_summary));
        return $this->t('<p>This will remove this @type from public display and remove references to it from all the places (articles, documents or stories) where it is currently used. These references will not be restored if you untrash this item later. Are you sure?</p><p>Affected content:<br /><ul>@usage_summary</ul></p>', $t_args);
      }
    }
    return $this->t('This will remove this @type, including already published versions, from public display anywhere. Are you sure?', $t_args);
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
      $entity = $this->getEntityFromRouteMatch();
    }

    $form['description'] = ['#markup' => $this->getQuestion()];
    $form['actions']['submit']['#ajax'] = [
      'callback' => [$this, 'ajaxCallbackConfirm'],
    ];
    // This is a special class to which JavaScript assigns dialog closing
    // behavior.
    $form['actions']['cancel']['#attributes']['class'][] = 'dialog-cancel';
    if ($entity instanceof MediaInterface && $entity->hasMandatoryReferences()) {
      $form['actions']['submit']['#access'] = FALSE;
    }
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
    $entity = $this->getEntityFromRouteMatch();
    if (!$entity) {
      throw new \Exception('Fatal error: Entity not found.');
    }

    // Build the status message for this operation.
    $message = $this->t('@type %title has been moved to the trash bin.', [
      '@type' => $entity->getBundleLabel(),
      '%title' => $entity->label(),
    ]);

    if ($entity instanceof MediaBase && $entity->hasMandatoryReferences()) {
      throw new \Exception('Cannot delete entity with mandatory references.');
    }
    if ($entity instanceof MediaBase && $entity->hasOptionalReferences()) {
      $usage_summary = $this->getUsageSummary($entity);
      $message .= '<br>' . $this->t('These content entities have been update:<ul>@usage_summary</ul>', [
        '@usage_summary' => Markup::create(implode('', $usage_summary)),
      ]);
    }
    $entity->setDeleted();
    $entity->save();

    // And inform the user.
    $this->messenger()->addStatus(Markup::create($message));

    $form_state->setRedirectUrl($entity->getOverviewUrl());
  }

  /**
   * Get a summary of the updates to be done.
   *
   * @param \Drupal\ncms_ui\Entity\MediaInterface $entity
   *   The media entity being deleted.
   *
   * @return array
   *   An array of strings containing markup.
   */
  private function getUsageSummary(MediaInterface $entity) {
    $affected_nodes = $entity->getNodesAffectedByDeletion();
    $usage_summary = array_map(function (NodeInterface $node) {
      $tags = $node instanceof ContentInterface ? $node->getTags() : [];
      $edit_link = $node->toLink('Edit', 'edit-form', [
        'attributes' => ['target' => '_blank'],
      ]);
      $additional_info = [];
      if (method_exists($node, 'getBundleLabel')) {
        $additional_info[] = $node->getBundleLabel();
      }
      if (!empty($tags)) {
        $additional_info[] = implode(', ', $tags);
      }

      $item = $node->label();
      if (!empty($additional_info)) {
        $item .= ' (' . implode(', ', $additional_info) . ')';
      }
      if ($edit_link->getUrl()->access()) {
        $item .= ' - ' . $edit_link->toString();
      }
      return '<li>' . $item . '</li>';
    }, $affected_nodes);
    return $usage_summary;
  }

}
