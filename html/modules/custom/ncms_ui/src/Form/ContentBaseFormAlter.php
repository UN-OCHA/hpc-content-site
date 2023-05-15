<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Entity\ContentBase;

/**
 * Form alter class for node forms of contentbase nodes.
 */
class ContentBaseFormAlter {

  use StringTranslationTrait;

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
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\ncms_ui\ContentSpaceManager $content_manager
   *   The content manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentSpaceManager $content_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->contentSpaceManager = $content_manager;
    $this->messenger = $messenger;
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
      $current_content_space = $this->contentSpaceManager->getCurrentContentSpace();
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
        $content_space_widget['#disabled'] = TRUE;
      }
    }
    else {
      // The content space of existing article can't be changed anymore.
      $form['field_content_space']['widget']['#access'] = FALSE;

      // Show the current revision number alongside the status.
      if ($entity instanceof ContentBase) {
        $form['meta']['published']['#markup'] = $this->t('#@version @status', [
          '@version' => $entity->getVersionId(),
          '@status' => $entity->getVersionStatus(),
        ]);
      }
    }
  }

}
