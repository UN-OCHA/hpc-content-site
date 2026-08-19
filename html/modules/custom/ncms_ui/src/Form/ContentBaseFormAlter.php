<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_publisher\PublisherManager;
use Drupal\ncms_ui\ContentRevisionWorkflow;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\EntityCompare;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form alter class for node forms of content base nodes.
 */
class ContentBaseFormAlter {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The publisher manager service.
   *
   * @var \Drupal\ncms_publisher\PublisherManager
   */
  protected $publisherManager;

  /**
   * The entity compare service.
   *
   * @var \Drupal\ncms_ui\Entity\EntityCompare
   */
  protected $entityCompare;

  /**
   * The content revision workflow service.
   *
   * @var \Drupal\ncms_ui\ContentRevisionWorkflow
   */
  protected $contentRevisionWorkflow;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\ncms_publisher\PublisherManager $publisher_manager
   *   The publisher manager service.
   * @param \Drupal\ncms_ui\Entity\EntityCompare $entity_compare
   *   The entity compare service.
   * @param \Drupal\ncms_ui\ContentRevisionWorkflow $content_revision_workflow
   *   The content revision workflow service.
   */
  public function __construct(RequestStack $request_stack, MessengerInterface $messenger, FormBuilderInterface $form_builder, PublisherManager $publisher_manager, EntityCompare $entity_compare, ContentRevisionWorkflow $content_revision_workflow) {
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
    $this->publisherManager = $publisher_manager;
    $this->entityCompare = $entity_compare;
    $this->contentRevisionWorkflow = $content_revision_workflow;

  }

  /**
   * Alter the node forms for content entities.
   */
  public function alterForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $entity */
    $entity = $form_object->getEntity();
    if (!$entity instanceof ContentInterface) {
      return;
    }

    if (!$entity->isNew()) {
      $form['meta']['published']['#markup'] = $this->t('#@version @status', [
        '@version' => $entity->getVersionId(),
        '@status' => $entity->getVersionStatusLabel(),
      ]);
    }

    // Make modifications to the submit buttons to support our custom
    // publishing/updating logic.
    $form_state->set('original_entity', $entity);
    $form['actions']['submit']['#access'] = FALSE;
    $form['actions']['delete']['#access'] = FALSE;
    $form['status']['#access'] = FALSE;
    $form['moderation_state']['#access'] = FALSE;
    $form['#submit'] = [[$this, 'submitForm']];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'ncms_ui/throbber';

    // Add a confirm field. This will be set by ContentSubmitConfirmForm.
    $form['confirmed'] = [
      '#type' => 'hidden',
      '#value' => 0,
    ];

    $ajax_confirm = [
      'callback' => [$this, 'ajaxConfirm'],
      'confirm_field' => 'confirmed',
      'progress' => ['type' => 'fullscreen'],
    ];

    switch ($entity->getContentStatus()) {
      case ContentBase::CONTENT_STATUS_DRAFT:
        $form['actions']['save_and_publish'] = [
          '#type' => 'submit',
          '#name' => 'save_and_publish',
          '#value' => $this->t('Save and publish'),
          '#ajax' => $ajax_confirm + [
            'confirm_question' => $this->t("Publishing the @type will make it publicly available on the API. It will also create a page for this article on Humanitarian Action, which will be set to 'Not displayed'.", [
              '@type' => strtolower($entity->type->entity->label()),
            ]),
          ],
          '#gin_action_item' => TRUE,
          '#submit' => [],
        ];
        break;

      case ContentBase::CONTENT_STATUS_PUBLISHED:
      case ContentBase::CONTENT_STATUS_PUBLISHED_WITH_DRAFT:
        $form['actions']['publish_correction'] = [
          '#type' => 'submit',
          '#name' => 'publish_correction',
          '#value' => $this->t('Publish as correction'),
          '#ajax' => $ajax_confirm + [
            'confirm_question' => $this->t('This will publish these changes as a correction to the currently published version, which will be entirely replaced. Are you sure?'),
          ],
          '#gin_action_item' => TRUE,
          '#submit' => [],
        ];
        $form['actions']['publish_revision'] = [
          '#type' => 'submit',
          '#name' => 'publish_revision',
          '#value' => $this->t('Publish as revision'),
          '#ajax' => $ajax_confirm + [
            'confirm_question' => $this->t('This will publish these changes as a new revision to the currently published version, which will remain publicly available as an earlier or original version. Are you sure?'),
          ],
          '#gin_action_item' => TRUE,
          '#submit' => [],
        ];
        break;
    }

    $form['actions']['save_draft'] = [
      '#type' => 'submit',
      '#name' => 'save_draft',
      '#value' => $this->t('Save as draft'),
      '#ajax' => $ajax_confirm,
      '#gin_action_item' => TRUE,
      '#submit' => [],
    ];
  }

  /**
   * Ajax callback to confirm the current action if necessary.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   */
  public function ajaxConfirm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      // Manually clear any errors.
      $response->addCommand(new InvokeCommand('form label.has-error', 'removeClass', ['has-error']));
      $response->addCommand(new InvokeCommand('form .error', 'removeClass', ['error']));
      $errors = $form_state->getErrors();
      foreach ($errors as $key => $error) {
        // Add the error message.
        $response->addCommand(new MessageCommand($error, NULL, [
          'type' => 'error',
        ], $key === array_key_first($errors)));
        $name = 'edit-' . Html::getClass($key);
        // And mark the form elements as having errors.
        $response->addCommand(new InvokeCommand('label[for="' . $name . '"]', 'addClass', ['has-error']));
        $response->addCommand(new InvokeCommand('[data-drupal-selector="' . $name . '"]', 'addClass', ['error']));
      }
      return $response;
    }

    // Clear error messages.
    $this->messenger->deleteByType('error');

    /** @var \Drupal\Core\Entity\EntityForm $form_object */
    $form_object = $form_state->getFormObject();

    /** @var \Drupal\ncms_ui\Entity\ContentInterface $original_entity */
    $original_entity = $form_state->get('original_entity');

    // Get the triggering element.
    $triggering_element = $form_state->getTriggeringElement();

    // Check if the entity has changes.
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $updated_entity */
    $updated_entity = $form_object->buildEntity($form, $form_state);
    $entity_updated = $this->entityCompare->hasChanged($updated_entity, $original_entity);

    $publish_actions = [
      'save_and_publish',
      'publish_correction',
      'publish_revision',
    ];
    if (in_array($triggering_element['#name'], $publish_actions) && !$updated_entity->hasTags()) {
      $content = $this->formBuilder->getForm(ContentSubmitNoTagsAlertForm::class, $triggering_element, $original_entity);
      $response->addCommand(new OpenModalDialogCommand($this->t('Publishing not possible'), $content, [
        'width' => '60vw',
      ]));
    }
    elseif ($original_entity->isPublished() && !$entity_updated && in_array($triggering_element['#name'], $publish_actions)) {
      $content = $this->formBuilder->getForm(ContentSubmitNoChangesAlertForm::class, $triggering_element, $original_entity);
      $response->addCommand(new OpenModalDialogCommand($this->t('Confirmation'), $content, [
        'width' => '60vw',
      ]));
    }
    else {
      $confirmed = $form_state->getUserInput()['confirmed'];
      $confirm_question = $triggering_element['#ajax']['confirm_question'] ?? NULL;
      if ($confirm_question && !$confirmed) {
        $content = $this->formBuilder->getForm(ContentSubmitConfirmForm::class, $triggering_element, $original_entity);
        $response->addCommand(new OpenModalDialogCommand($this->t('Confirmation'), $content, [
          'width' => '60vw',
        ]));
      }
      elseif (!$confirmed) {
        // No confirmation needed.
        $confirmed = TRUE;
      }

      if ($confirmed) {
        // Submit the form.
        $this->submitForm($form, $form_state, TRUE);

        // Remove any is_changed class just in case to prevent the beforeunload
        // event set up by layout paragraphs to trigger a browser warning. See
        // https://git.drupalcode.org/project/layout_paragraphs/-/blame/2.0.x/js/builder.js#L182
        $response->addCommand(new InvokeCommand('.is_changed', 'removeClass', ['is_changed']));

        // Go back to the publisher or to the backend listings page.
        $redirect_url = $this->publisherManager->getCurrentRedirectUrl() ?? $updated_entity->getOverviewUrl()->toString();
        $response->addCommand(new AppendCommand('body', '<div class="ajax-progress ajax-progress--fullscreen"><div class="ajax-progress__throbber ajax-progress__throbber--fullscreen">&nbsp;</div></div>'));
        $response->addCommand(new RedirectCommand($redirect_url));
      }
    }

    return $response;
  }

  /**
   * Submit callback.
   */
  public function submitForm(array &$form, FormStateInterface $form_state, $submit_in_ajax_context = FALSE) {
    if ($this->requestStack->getCurrentRequest()->isXmlHttpRequest() && !$submit_in_ajax_context) {
      // If this submission is done via ajax, we first need to do the
      // confirmation.
      return;
    }

    /** @var \Drupal\Core\Entity\EntityForm $form_object */
    $form_object = $form_state->getFormObject();

    /** @var \Drupal\ncms_ui\Entity\ContentInterface $original_entity */
    $original_entity = $form_state->get('original_entity');

    // Get the triggering element.
    $triggering_element = $form_state->getTriggeringElement();

    // Check if the entity has changes.
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $updated_entity */
    $updated_entity = $form_object->buildEntity($form, $form_state);
    $entity_updated = $this->entityCompare->hasChanged($updated_entity, $original_entity);

    // What happens next depends on the used submit button and whether the
    // entity has changed or not.
    $workflow_result = ContentRevisionWorkflow::RESULT_NO_ACTION;
    switch ($triggering_element['#name']) {
      case 'save_and_publish':
        $workflow_result = $this->contentRevisionWorkflow->saveAndPublish($updated_entity, $original_entity, $entity_updated);
        break;

      case 'publish_correction':
        $workflow_result = $this->contentRevisionWorkflow->publishCorrection($updated_entity, $original_entity, $entity_updated);
        break;

      case 'publish_revision':
        $workflow_result = $this->contentRevisionWorkflow->publishRevision($updated_entity, $original_entity, $entity_updated);
        break;

      case 'save_draft':
        $workflow_result = $this->contentRevisionWorkflow->saveDraft($updated_entity, $entity_updated);
        break;
    }

    $this->addWorkflowResultMessage($workflow_result, $updated_entity);
    $form_state->setRedirectUrl($updated_entity->getOverviewUrl());
  }

  /**
   * Adds the editor-facing message for a workflow result.
   *
   * @param string $workflow_result
   *   One of the \Drupal\ncms_ui\ContentRevisionWorkflow RESULT_* constants.
   * @param \Drupal\ncms_ui\Entity\ContentInterface $updated_entity
   *   The updated entity.
   */
  private function addWorkflowResultMessage(string $workflow_result, ContentInterface $updated_entity): void {
    switch ($workflow_result) {
      case ContentRevisionWorkflow::RESULT_CREATED_PUBLISHED_CORRECTION:
        $this->messenger->addStatus($this->t('Created and published a new version of <em>@label</em>. Unpublished the last published version.', [
          '@label' => $updated_entity->label(),
        ]));
        break;

      case ContentRevisionWorkflow::RESULT_CREATED_PUBLISHED_VERSION:
        $this->messenger->addStatus($this->t('Created and published a new version of <em>@label</em>.', [
          '@label' => $updated_entity->label(),
        ]));
        break;

      case ContentRevisionWorkflow::RESULT_PUBLISHED_CURRENT_VERSION:
        $this->messenger->addStatus($this->t('Published current version of <em>@label</em>.', [
          '@label' => $updated_entity->label(),
        ]));
        break;

      case ContentRevisionWorkflow::RESULT_SAVED_DRAFT:
        $this->messenger->addStatus($this->t('Saved a new draft version of <em>@label</em>.', [
          '@label' => $updated_entity->label(),
        ]));
        break;

      case ContentRevisionWorkflow::RESULT_NO_CHANGES:
        $this->messenger->addStatus($this->t('No changes detected for <em>@label</em>. The @type has not been updated.', [
          '@label' => $updated_entity->label(),
          '@type' => strtolower($updated_entity->type->entity->label()),
        ]));
        break;
    }
  }

}
