<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_publisher\PublisherManager;
use Drupal\ncms_ui\ContentSpaceManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form alter class for node forms of content base nodes.
 */
class MediaBaseFormAlter {

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
    /** @var \Drupal\ncms_ui\Entity\Media\MediaBase $entity */
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
        $content_space_widget['#default_value'] = [$current_content_space => $current_content_space];
        $content_space_widget['#access'] = FALSE;
      }
    }
    else {
      // The content space of existing article can't be changed anymore.
      $content_space = $entity->getContentSpace();
      if (!empty($content_space)) {
        $form['field_content_space']['widget']['#access'] = FALSE;
      }

    }
  }

}
