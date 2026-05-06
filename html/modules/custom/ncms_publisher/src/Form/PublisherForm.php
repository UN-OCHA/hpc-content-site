<?php

namespace Drupal\ncms_publisher\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a PublisherForm to edit Publisher config entities.
 */
class PublisherForm extends EntityForm {

  /**
   * The originally configured refresh secret.
   *
   * @var string|null
   */
  protected $originalRefreshSecret;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface $publisher */
    $publisher = $this->entity;
    $this->originalRefreshSecret = $publisher->getRefreshSecret();
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

    $form['refresh_notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Refresh notifications'),
      '#open' => $publisher->refreshNotificationsEnabled(),
    ];

    $form['refresh_notifications']['refresh_notifications_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send refresh notifications'),
      '#default_value' => $publisher->refreshNotificationsEnabled(),
    ];

    $form['refresh_notifications']['refresh_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Refresh endpoint'),
      '#default_value' => $publisher->getRefreshEndpoint(),
      '#states' => [
        'visible' => [
          ':input[name="refresh_notifications_enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="refresh_notifications_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['refresh_notifications']['refresh_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Refresh secret'),
      '#description' => $this->t('No refresh secret is currently set.'),
      '#default_value' => $publisher->getRefreshSecret(),
      '#states' => [
        'visible' => [
          ':input[name="refresh_notifications_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->originalRefreshSecret) {
      $form['refresh_notifications']['refresh_secret']['#description'] = $this->t('A refresh secret is currently set. Enter a new value to replace it, or leave this field empty to keep the current one.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!$form_state->getValue('refresh_notifications_enabled')) {
      return;
    }

    if (!$form_state->getValue('refresh_endpoint')) {
      $form_state->setErrorByName('refresh_endpoint', $this->t('The refresh endpoint is required when refresh notifications are enabled.'));
    }

    if (!$form_state->getValue('refresh_secret') && !$this->originalRefreshSecret) {
      $form_state->setErrorByName('refresh_secret', $this->t('The refresh secret is required when refresh notifications are enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface $publisher */
    $publisher = $this->entity;
    $publisher->set('known_hosts', $form_state->getValue('known_hosts'));
    $publisher->set('refresh_notifications_enabled', (bool) $form_state->getValue('refresh_notifications_enabled'));
    $publisher->set('refresh_endpoint', $form_state->getValue('refresh_endpoint'));
    $refresh_secret = $form_state->getValue('refresh_secret') ?: $this->originalRefreshSecret;
    $publisher->set('refresh_secret', $refresh_secret);
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
