<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_publisher\PublisherManager;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content manager.
   *
   * @var \Drupal\ncms_ui\ContentSpaceManager
   */
  protected $contentSpaceManager;

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
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\ncms_publisher\PublisherManager $publisher_manager
   *   The publisher manager service.
   */
  public function __construct(RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, ContentSpaceManager $content_manager, MessengerInterface $messenger, FormBuilderInterface $form_builder, PublisherManager $publisher_manager) {
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSpaceManager = $content_manager;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
    $this->publisherManager = $publisher_manager;
  }

  /**
   * Alter the replicate confirm form.
   */
  public function alterForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    // Check if this is a new node.
    if ($entity->isNew()) {
      $content_space_ids = $this->contentSpaceManager->getValidContentSpaceIdsForCurrentUser();
      $current_content_space = $this->contentSpaceManager->getCurrentContentSpaceId();
      if (empty($content_space_ids)) {
        // This user needs a content space first and is currently not allowed to
        // create new content.
        $this->messenger->addWarning($this->t('You are currently not allowed to create content. Please contact our support.'));
        $form['#disabled'] = TRUE;
        $form['field_content_space']['widget']['#access'] = FALSE;
      }
      elseif (!in_array($current_content_space, $content_space_ids)) {
        // The user can't create content in the currently selected content
        // space.
        $this->messenger->addWarning($this->t('You are not allowed to create content in the current content space. Please switch to another content space.'));
        $form['#disabled'] = TRUE;
        $form['field_content_space']['widget']['#access'] = FALSE;
      }
      else {
        $content_space_widget = &$form['field_content_space']['widget'];
        $content_space_widget['#options'] = array_intersect_key($content_space_widget['#options'], [$current_content_space => $current_content_space]);
        $content_space_widget['#default_value'] = [$current_content_space => $current_content_space];
        $content_space_widget['#access'] = FALSE;
      }
    }
    else {
      // The content space of existing article can't be changed anymore.
      $content_spaces = $entity->get('field_content_space')->referencedEntities();
      if (!empty($content_spaces)) {
        $form['field_content_space']['widget']['#access'] = FALSE;
      }

      // Show the current revision number alongside the status.
      if ($entity instanceof ContentBase) {
        $form['meta']['published']['#markup'] = $this->t('#@version @status', [
          '@version' => $entity->getVersionId(),
          '@status' => $entity->getVersionStatusLabel(),
        ]);
      }
    }
    $content_space = $this->contentSpaceManager->getCurrentContentSpace();
    if ($content_space) {
      $form['field_tags']['widget']['target_id']['#description'] .= ' ' . $this->t('Tags inherited from the content space: <em>@tags</em>', [
        '@tags' => implode(', ', $content_space->getTags()),
      ]);
    }

    // Make modifications to the submit buttons to support our custom
    // publishing/updating logic.
    if ($entity instanceof ContentBase) {
      $form_state->set('original_entity', $entity);
      $form_state->setRedirectUrl($entity->getOverviewUrl());
      $form['actions']['submit']['#access'] = FALSE;
      $form['actions']['delete']['#access'] = FALSE;
      $form['status']['#access'] = FALSE;
      $form['moderation_state']['#access'] = FALSE;
      $form['#submit'] = [[$this, 'submitForm']];
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

      // Add a confirm field. This will be set by ContentSubmitConfirmForm.
      $form['confirmed'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];

      $ajax_confirm = [
        'callback' => [$this, 'ajaxConfirm'],
        'confirm_field' => 'confirmed',
      ];

      switch ($entity->getContentStatus()) {
        case ContentBase::CONTENT_STATUS_DRAFT:
          $form['actions']['save_and_publish'] = [
            '#type' => 'submit',
            '#name' => 'save_and_publish',
            '#value' => $this->t('Save and publish'),
            '#ajax' => $ajax_confirm + [
              'confirm_question' => $this->t('This will make this @type publicly available on the API and will automatically create a page for this @type on Humanitarian Action. Are you sure?', [
                '@type' => strtolower($entity->type->entity->label()),
              ]),
            ],
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
          ];
          $form['actions']['publish_revision'] = [
            '#type' => 'submit',
            '#name' => 'publish_revision',
            '#value' => $this->t('Publish as revision'),
            '#ajax' => $ajax_confirm + [
              'confirm_question' => $this->t('This will publish these changes as a new revision to the currently published version, which will remain publicly available as an earlier or original version. Are you sure?'),
            ],
          ];
          break;
      }

      $form['actions']['save_draft'] = [
        '#type' => 'submit',
        '#name' => 'save_draft',
        '#value' => $this->t('Save as draft'),
        '#ajax' => $ajax_confirm,
      ];
    }
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

    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $original_entity */
    $original_entity = $form_state->get('original_entity');

    // Get the triggering element.
    $triggering_element = $form_state->getTriggeringElement();

    // Check if the entity has changes.
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $updated_entity */
    $updated_entity = $form_object->buildEntity($form, $form_state);
    $entity_updated = $this->hashEntity($updated_entity) != $this->hashEntity($original_entity);

    $publish_actions = [
      'publish_correction',
      'publish_revision',
    ];
    if ($original_entity->isPublished() && !$entity_updated && in_array($triggering_element['#name'], $publish_actions)) {
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

        // Go back to the publisher or to the backend listings page.
        $redirect_url = $this->publisherManager->getCurrentRedirectUrl() ?? $updated_entity->getOverviewUrl()->toString();
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

    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $original_entity */
    $original_entity = $form_state->get('original_entity');

    // Get the triggering element.
    $triggering_element = $form_state->getTriggeringElement();

    // Check if the entity has changes.
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $updated_entity */
    $updated_entity = $form_object->buildEntity($form, $form_state);
    $entity_updated = $this->hashEntity($updated_entity) != $this->hashEntity($original_entity);

    /** @var \Drupal\ncms_ui\Entity\Storage\ContentStorage $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // What happens next depends on the used submit button and whether the
    // entity has changed or not.
    switch ($triggering_element['#name']) {
      case 'save_and_publish':
        if ($entity_updated) {
          // Entity has been changed. We create a new revision and set it to
          // publish.
          $updated_entity->setPublished();
          $updated_entity->save();
          // Add a message.
          $this->messenger->addStatus($this->t('Created and published a new version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        else {
          // Entity has not been changed, so we simply update the current
          // revision to published.
          $node_storage->updateRevisionStatus($original_entity, NodeInterface::PUBLISHED);
          // Add a message.
          $this->messenger->addStatus($this->t('Published current version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        break;

      case 'publish_correction':
        $last_published = $updated_entity->getLastPublishedRevision();
        if ($entity_updated) {
          // Entity has been updated. Unpublish the last published revision.
          $node_storage->updateRevisionStatus($last_published, NodeInterface::NOT_PUBLISHED);
          // Create a new revision and set to published.
          $updated_entity->setPublished();
          $updated_entity->save();
          // Add a message.
          $this->messenger->addStatus($this->t('Created and published a new version of <em>@label</em>. Unpublished the last published version.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        elseif (!$updated_entity->isPublished()) {
          // No changes and current entity is unpublished. Just publish it
          // without a new revision.
          $node_storage->updateRevisionStatus($original_entity, NodeInterface::PUBLISHED);
          // Add a message.
          $this->messenger->addStatus($this->t('Published current version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        break;

      case 'publish_revision':
        if ($entity_updated) {
          // Entity has changes. Create a new revision and publish it. The
          // last published revision stays published.
          $updated_entity->setPublished();
          $updated_entity->save();
          // Add a message.
          $this->messenger->addStatus($this->t('Created and published a new version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        elseif (!$updated_entity->isPublished()) {
          // No changes and current entity is unpublished. Just publish it
          // without a new revision.
          $node_storage->updateRevisionStatus($original_entity, NodeInterface::PUBLISHED);
          // Add a message.
          $this->messenger->addStatus($this->t('Published current version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        break;

      case 'save_draft':
        if ($entity_updated) {
          // Entity has been changed, just make sure it's unpublished, create
          // a new revision and save.
          $updated_entity->setUnpublished();
          $updated_entity->save();
          // Add a message.
          $this->messenger->addStatus($this->t('Saved a new draft version of <em>@label</em>.', [
            '@label' => $updated_entity->label(),
          ]));
        }
        else {
          $this->messenger->addStatus($this->t('No changes detected for <em>@label</em>. The @type has not been updated.', [
            '@label' => $updated_entity->label(),
            '@type' => strtolower($updated_entity->type->entity->label()),
          ]));
        }
        break;
    }

    $form_state->setRedirectUrl($updated_entity->getOverviewUrl());
  }

  /**
   * Calculate a hash for the current state of the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object to calculate the hash for.
   *
   * @return string
   *   An md5 hash.
   */
  public function hashEntity(ContentEntityInterface $entity) {
    // First make sure that referenced entities are fully loaded.
    $entity_data = $entity->toArray();
    foreach ($entity_data as $field_name => &$field) {
      if (empty($field) || strpos($field_name, 'field_') !== 0) {
        continue;
      }
      foreach ($field as $delta => &$item) {
        if (array_key_exists('target_revision_id', $item) && !array_key_exists('entity', $item)) {
          $item['entity'] = $entity->get($field_name)->referencedEntities()[$delta];
        }
      }
    }
    $remove_keys = [
      'vid',
      'changed',
      'revision_id',
      'revision_timestamp',
      'revision_uid',
      'revision_translation_affected',
      'status',
      'comment',
      'path',
    ];
    self::reduceArray($entity_data, $remove_keys);
    return md5(str_replace(['"', "\n"], '', json_encode($entity_data)));
  }

  /**
   * Reduce an array by removing empty items.
   *
   * @param array $array
   *   The input array.
   * @param array $remove_keys
   *   The array with keys to remove.
   */
  public static function reduceArray(array &$array, array $remove_keys = []) {
    foreach ($array as $key => &$a) {
      if (!empty($remove_keys) && in_array($key, $remove_keys)) {
        unset($array[$key]);
        continue;
      }
      if (is_array($a)) {
        if (empty($a)) {
          unset($array[$key]);
        }
        else {
          self::reduceArray($a, $remove_keys);
        }
      }
      if (is_object($a)) {
        if ($a instanceof ContentEntityInterface) {
          $array[$key] = $a->toArray();
          self::reduceArray($array[$key], $remove_keys);
        }
        else {
          unset($array[$key]);
        }
      }
      if (is_bool($a)) {
        $a = $a ? 1 : 0;
      }
    }
    $array = array_filter($array);
    ksort($array);
  }

}
