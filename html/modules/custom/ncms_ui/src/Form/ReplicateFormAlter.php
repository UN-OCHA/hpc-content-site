<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ncms_ui\ContentManager;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;

/**
 * Form alter class for the replicate cofirm form.
 */
class ReplicateFormAlter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content manager.
   *
   * @var \Drupal\ncms_ui\ContentManager
   */
  protected $contentManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentManager $content_manager
   *   The content manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentManager $content_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentManager = $content_manager;
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

    $widget = $form_display->getRenderer('field_content_space');
    $items = $entity->get('field_content_space');
    $items->filterEmptyItems();
    $form['field_content_space'] = $widget->form($items, $form, $form_state);

    if ($this->contentManager->shouldRestrictContentSpaces()) {
      $content_space_ids = $this->contentManager->getValidContentSpaceIdsForCurrentUser();
      $content_space_widget = &$form['field_content_space']['widget'];
      $content_space_widget['#options'] = array_intersect_key($content_space_widget['#options'], $content_space_ids + ['_none' => TRUE]);
    }

    $form['field_content_space']['#weight'] = -1;
    $form['description']['#access'] = FALSE;
  }

}
