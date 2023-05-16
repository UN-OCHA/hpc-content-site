<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;

/**
 * Form alter class for the replicate confirm form.
 */
class ReplicateFormAlter implements TrustedCallbackInterface {

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
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentSpaceManager $content_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSpaceManager = $content_manager;
  }

  /**
   * Alter the replicate confirm form.
   */
  public function alterForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\replicate_ui\Form\ReplicateConfirmForm $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    if (!$entity instanceof ContentSpaceAwareInterface) {
      return;
    }
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default');
    $form_state->set('form_display', $form_display);

    // Add the content space selector to select into which content type the
    // entity should be replicated.
    $widget = $form_display->getRenderer('field_content_space');
    if ($widget) {
      $items = $entity->get('field_content_space');
      $items->filterEmptyItems();
      $form['field_content_space'] = $widget->form($items, $form, $form_state);
      $form['field_content_space']['#weight'] = -1;
      $content_space_ids = $this->contentSpaceManager->getValidContentSpaceIdsForCurrentUser();
      $content_space_widget = &$form['field_content_space']['widget'];
      $content_space_widget['#options'] = array_intersect_key($content_space_widget['#options'], $content_space_ids + ['_none' => TRUE]);
      $content_space_widget['#default_value'] = $this->contentSpaceManager->getCurrentContentSpaceId();
      if (count($content_space_widget['#options']) == 1) {
        $content_space_widget['#disabled'] = TRUE;
      }

      array_unshift($form['actions']['submit']['#submit'], [
        self::class, 'submitNodeFormToContentSpace',
      ]);
    }

    // No description needed.
    $form['description']['#access'] = FALSE;

    // Cancel link should go back to the front page.
    $form['actions']['cancel']['#url'] = Url::fromRoute('<front>');

  }

  /**
   * Custom submit handler to set the current content space.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitNodeFormToContentSpace(array &$form, FormStateInterface $form_state) {
    $content_space_manager = self::getContentSpaceManager();
    // Switch to the target content space if necessary.
    $element_parents = [
      'field_content_space',
      0,
      'target_id',
    ];
    $target_content_space = $form_state->getValue($element_parents);
    if ($content_space_manager->getCurrentContentSpaceId() != $target_content_space) {
      $content_space_manager->setCurrentContentSpaceId($target_content_space);
    }
  }

  /**
   * Get the content space manager.
   *
   * @return \Drupal\ncms_ui\ContentSpaceManager
   *   The content space manager service.
   */
  private static function getContentSpaceManager() {
    return \Drupal::service('ncms_ui.content.manager');
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['submitNodeFormToContentSpace'];
  }

}
