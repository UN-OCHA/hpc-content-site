<?php

namespace Drupal\ncms_publisher\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a PublisherForm to edit Publisher config entities.
 */
class PublisherForm extends EntityForm {

  /**
   * The publisher refresh client.
   *
   * @var \Drupal\ncms_publisher\PublisherRefreshClient
   */
  protected $refreshClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->refreshClient = $container->get('ncms_publisher.refresh_client');
    return $instance;
  }

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
        'disabled' => [
          ':input[name="refresh_notifications_enabled"]' => ['checked' => FALSE],
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
        'disabled' => [
          ':input[name="refresh_notifications_enabled"]' => ['checked' => FALSE],
        ],
      ],
    ];

    if ($publisher->getRefreshSecret()) {
      $form['refresh_notifications']['refresh_secret']['#description'] = $this->t('A refresh secret is currently set. Enter a new value to replace it, or leave this field empty to keep the current one.');
    }

    $runtime_refresh_notifications_enabled = $this->getRuntimeRefreshSetting('refresh_notifications_enabled');
    $runtime_refresh_endpoint = $this->getRuntimeRefreshSetting('refresh_endpoint');
    $runtime_refresh_secret = $this->getRuntimeRefreshSetting('refresh_secret');
    $form['refresh_notifications']['connection_check_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Connection check'),
      '#markup' => $runtime_refresh_notifications_enabled
        ? $this->t('Uses runtime refresh configuration. Endpoint: @endpoint. Secret: @secret.', [
          '@endpoint' => $runtime_refresh_endpoint ?: $this->t('Not set'),
          '@secret' => $runtime_refresh_secret ? $this->t('Set') : $this->t('Not set'),
        ])
        : $this->t('Connection check is unavailable because refresh notifications are not enabled in the runtime configuration.'),
    ];

    $form['refresh_notifications']['check_connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check connection'),
      '#disabled' => !$runtime_refresh_notifications_enabled,
      '#submit' => ['::checkRefreshConnection'],
      '#limit_validation_errors' => [
        ['refresh_endpoint'],
        ['refresh_secret'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!(bool) $form_state->getValue('refresh_notifications_enabled')) {
      return;
    }

    if (!$form_state->getValue('refresh_endpoint')) {
      $form_state->setErrorByName('refresh_endpoint', $this->t('The refresh endpoint is required when refresh notifications are enabled.'));
    }

    $refresh_secret = $this->getRefreshSecret($form_state);
    if (!$refresh_secret) {
      $form_state->setErrorByName('refresh_secret', $this->t('The refresh secret is required when refresh notifications are enabled.'));
    }
    elseif (!$this->getSubmittedRefreshSetting($form_state, 'refresh_secret')) {
      // The password element intentionally renders empty, so an unchanged
      // secret is submitted as an empty value. At this point we have already
      // resolved the value that will be used at runtime: either the stored
      // secret or an override from file-based configuration. Put that value
      // back into form state before EntityForm::submitForm() copies submitted
      // values to the config entity; otherwise the later entity build step
      // would treat the empty password submission as an intentional deletion.
      $form_state->setValue('refresh_secret', $refresh_secret);
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
    $publisher->set('refresh_endpoint', $form_state->getValue('refresh_endpoint') ?: NULL);
    $publisher->set('refresh_secret', $this->getRefreshSecret($form_state));
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

  /**
   * Submit handler for checking the refresh webhook connection.
   */
  public function checkRefreshConnection(array &$form, FormStateInterface $form_state): void {
    $endpoint = $this->getRuntimeRefreshSetting('refresh_endpoint');
    $secret = $this->getRuntimeRefreshSetting('refresh_secret');
    if (!$endpoint || !$secret) {
      $this->messenger()->addError($this->t('Enter a refresh endpoint and refresh secret before checking the connection.'));
      $form_state->setRebuild();
      return;
    }

    try {
      $response = $this->refreshClient->post($endpoint, $secret, $this->refreshClient->buildPingPayload(), [
        'http_errors' => FALSE,
      ]);
      if ($response->getStatusCode() === Response::HTTP_ACCEPTED) {
        $this->messenger()->addStatus($this->t('Refresh webhook connection check succeeded.'));
      }
      else {
        $this->messenger()->addError($this->t('Refresh webhook connection check failed with HTTP status @status.', [
          '@status' => $response->getStatusCode(),
        ]));
      }
    }
    catch (GuzzleException $e) {
      $this->messenger()->addError($this->t('Refresh webhook connection check failed: @message', [
        '@message' => $e->getMessage(),
      ]));
    }

    $form_state->setRebuild();
  }

  /**
   * Get the submitted refresh setting value.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $key
   *   The refresh setting key.
   *
   * @return mixed
   *   The submitted refresh setting value.
   */
  private function getSubmittedRefreshSetting(FormStateInterface $form_state, string $key) {
    $value = $form_state->getValue($key);
    return $key === 'refresh_notifications_enabled' ? (bool) $value : ($value ?: NULL);
  }

  /**
   * Get the refresh setting value that is active at runtime.
   *
   * Values returned by the config factory include file-based overrides from
   * settings.php. That is intentionally different from the raw value shown in
   * the editable form field, because the connection check must use the same
   * configuration the refresh client will use when notifications are sent.
   *
   * @param string $key
   *   The refresh setting key.
   *
   * @return mixed
   *   The runtime refresh setting value.
   */
  private function getRuntimeRefreshSetting(string $key) {
    $value = $this->config($this->entity->getConfigDependencyName())->get($key);
    return $key === 'refresh_notifications_enabled' ? (bool) $value : ($value ?: NULL);
  }

  /**
   * Get the secret to use for validation or connection checks.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return string|null
   *   The refresh secret or NULL.
   */
  private function getRefreshSecret(FormStateInterface $form_state): ?string {
    // Prefer a newly submitted secret, because that is the administrator's
    // explicit replacement value. If the password field was left empty, fall
    // back to the stored entity value and then to runtime config so a
    // settings.php override is also accepted as a configured secret.
    return $this->getSubmittedRefreshSetting($form_state, 'refresh_secret')
      ?: $this->entity->getRefreshSecret()
      ?: $this->getRuntimeRefreshSetting('refresh_secret');
  }

}
