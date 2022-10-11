<?php

namespace Drupal\ncms_publisher\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a PublisherForm to edit Publisher config entities.
 */
class PublisherForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface $publisher */
    $publisher = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $publisher->label(),
      '#description' => $this->t('Label for the Publisher.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $publisher->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ncms_publisher\Entity\Publisher::load',
      ],
      '#disabled' => !$publisher->isNew(),
    ];

    $form['known_hosts'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Known hosts'),
      '#maxlength' => 255,
      '#default_value' => implode("\n", $publisher->getKnownHosts()),
      '#description' => $this->t('Known hosts of the Publisher. Enter one host per line.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface $publisher */
    $publisher = $this->entity;
    $publisher->set('known_hosts', $form_state->getValue('known_hosts'));
    $status = $publisher->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Publisher.', [
          '%label' => $publisher->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Publisher.', [
          '%label' => $publisher->label(),
        ]));
    }
    $form_state->setRedirectUrl($publisher->toUrl('collection'));
  }

}
