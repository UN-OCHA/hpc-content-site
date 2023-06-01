<?php

namespace Drupal\ncms_ui\Form;

use Drupal\content_moderation\Entity\ContentModerationState;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\Ajax\ReloadPageCommand;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form class for content submit confirm forms.
 */
class ContentRestoreForm extends ConfirmFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_restore_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->getNodeFromRouteMatch();
    return $this->t('This will restore this @type and make it automatically publicly available again if there are any published versions. Are you sure?', [
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
    $entity = $this->getNodeFromRouteMatch()->getLatestRevision();
    $last_published = $entity->getLastPublishedRevision();

    // Confirmed, so we can delete the revision where the node got marked as
    // deleted. First we need to set the previous revision to be the default
    // one.
    $new_default_revision = $entity->getPreviousRevision();
    $new_default_revision->isDefaultRevision(empty($last_published));
    $new_default_revision->setNewRevision(FALSE);
    $new_default_revision->setSyncing(TRUE);
    $new_default_revision->save();

    if ($last_published) {
      $last_published->isDefaultRevision(TRUE);
      $last_published->setNewRevision(FALSE);
      $last_published->setSyncing(TRUE);
      $last_published->save();
    }

    // Do the same for the content moderation entity.
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($new_default_revision);
    if ($content_moderation_state) {
      $content_moderation_state->isDefaultRevision(TRUE);
      $content_moderation_state->setNewRevision(FALSE);
      $content_moderation_state->setSyncing(TRUE);
      ContentModerationState::updateOrCreateFromEntity($content_moderation_state);
    }

    // And then finally delete the latest revision, which is the one that
    // marked the whole entity as being deleted.
    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_storage->deleteRevision($entity->getRevisionId());

    // And inform the user.
    $this->messenger()->addStatus($this->t('@type %title has been restored from the trash bin.', [
      '@type' => $entity->type->entity->label(),
      '%title' => $entity->label(),
    ]));

    $form_state->setRedirectUrl($entity->getOverviewUrl());
  }

  /**
   * Get the current entity from the route match.
   *
   * @return \Drupal\ncms_ui\Entity\Content\ContentBase|null
   *   The entity or NULL.
   */
  private function getNodeFromRouteMatch() {
    $entity = $this->getRouteMatch()->getParameter('node');
    return $entity && $entity instanceof ContentBase ? $entity : NULL;
  }

}
