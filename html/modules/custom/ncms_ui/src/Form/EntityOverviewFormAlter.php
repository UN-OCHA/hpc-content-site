<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ncms_ui\Entity\EntityOverviewInterface;

/**
 * Form alter class for node forms of content base nodes.
 */
class EntityOverviewFormAlter {

  /**
   * Alter the entity form.
   */
  public function alterForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();
    if (!$entity instanceof EntityOverviewInterface) {
      return;
    }
    $form_state->setRedirectUrl($entity->getOverviewUrl());
  }

}
