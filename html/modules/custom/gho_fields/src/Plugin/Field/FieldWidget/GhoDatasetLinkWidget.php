<?php

namespace Drupal\gho_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'gho_dataset_link' widget.
 *
 * @FieldWidget(
 *   id = "gho_dataset_link",
 *   label = @Translation("GHO dataset link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class GhoDatasetLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['title']['#title'] = $this->t('Name');
    return $element;
  }

}
