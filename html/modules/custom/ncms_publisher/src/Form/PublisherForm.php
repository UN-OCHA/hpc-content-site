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
      '#tree' => TRUE,
    ];

    $form['refresh_notifications']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send refresh notifications'),
      '#default_value' => $publisher->refreshNotificationsEnabled(),
    ];

    $form['refresh_notifications']['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Refresh endpoint'),
      '#default_value' => $publisher->getRefreshEndpoint(),
      '#states' => [
        'disabled' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => FALSE],
        ],
        'required' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['refresh_notifications']['secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Refresh secret'),
      '#description' => $this->t('No refresh secret is currently set.'),
      '#default_value' => $publisher->getRefreshSecret(),
      '#states' => [
        'disabled' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    if ($publisher->getRefreshSecret()) {
      $form['refresh_notifications']['secret']['#description'] = $this->t('A refresh secret is currently set. Enter a new value to replace it, or leave this field empty to keep the current one.');
    }

    $refresh_basic_auth = $publisher->getRefreshBasicAuth();
    $form['refresh_notifications']['basic_auth'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic auth'),
      '#open' => !empty($refresh_basic_auth),
      '#tree' => TRUE,
      '#states' => [
        'disabled' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['refresh_notifications']['basic_auth']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#description' => $this->t('Enter the basic auth username.'),
      '#default_value' => $refresh_basic_auth['user'] ?? NULL,
      '#states' => [
        'disabled' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['refresh_notifications']['basic_auth']['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter the basic auth password.'),
      '#default_value' => $refresh_basic_auth['pass'] ?? NULL,
      '#states' => [
        'disabled' => [
          ':input[name="refresh_notifications[enabled]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $runtime_refresh_notifications_enabled = $this->getRuntimeRefreshSetting('enabled');
    $runtime_refresh_endpoint = $this->getRuntimeRefreshSetting('endpoint');
    $runtime_refresh_secret = $this->getRuntimeRefreshSetting('secret');
    $runtime_refresh_basic_auth = $this->getRuntimeRefreshSetting('basic_auth');
    $runtime_refresh_basic_auth_is_set = !empty($runtime_refresh_basic_auth['user']) || !empty($runtime_refresh_basic_auth['pass']);
    $form['refresh_notifications']['connection_check_info'] = [
      '#type' => 'item',
      '#title' => $this->t('Connection check'),
      '#markup' => $runtime_refresh_notifications_enabled
        ? $this->t('Uses runtime refresh configuration. Endpoint: @endpoint. Secret: @secret. Basic auth: @basic_auth.', [
          '@endpoint' => $runtime_refresh_endpoint ?: $this->t('Not set'),
          '@secret' => $runtime_refresh_secret ? $this->t('Set') : $this->t('Not set'),
          '@basic_auth' => $runtime_refresh_basic_auth_is_set ? $this->t('Set') : $this->t('Not set'),
        ])
        : $this->t('Connection check is unavailable because refresh notifications are not enabled in the runtime configuration.'),
    ];

    $form['refresh_notifications']['check_connection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check connection'),
      '#disabled' => !$runtime_refresh_notifications_enabled,
      '#submit' => ['::checkRefreshConnection'],
      '#limit_validation_errors' => [
        ['refresh_notifications', 'endpoint'],
        ['refresh_notifications', 'secret'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // This method currently only adds validation for refresh settings, so
    // there is nothing more to check when refresh notifications are disabled.
    if (!(bool) $this->getSubmittedRefreshSetting($form_state, 'enabled')) {
      return;
    }

    if (!$this->getSubmittedRefreshSetting($form_state, 'endpoint')) {
      $form_state->setErrorByName('refresh_notifications][endpoint', $this->t('The refresh endpoint is required when refresh notifications are enabled.'));
    }

    $refresh_secret = $this->getRefreshSecret($form_state);
    if (!$refresh_secret) {
      $form_state->setErrorByName('refresh_notifications][secret', $this->t('The refresh secret is required when refresh notifications are enabled.'));
    }
    elseif (!$this->getSubmittedRefreshSetting($form_state, 'secret')) {
      // The password element intentionally renders empty, so an unchanged
      // secret is submitted as an empty value. At this point we have already
      // resolved the value that will be used at runtime: either the stored
      // secret or an override from file-based configuration. Put that value
      // back into form state before EntityForm::submitForm() copies submitted
      // values to the config entity; otherwise the later entity build step
      // would treat the empty password submission as an intentional deletion.
      $refresh_notifications = $form_state->getValue('refresh_notifications') ?: [];
      $refresh_notifications['secret'] = $refresh_secret;
      $form_state->setValue('refresh_notifications', $refresh_notifications);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\ncms_publisher\Entity\PublisherInterface $publisher */
    $publisher = $this->entity;
    $publisher->set('known_hosts', $form_state->getValue('known_hosts'));
    $publisher->set('refresh_notifications', [
      'enabled' => (bool) $this->getSubmittedRefreshSetting($form_state, 'enabled'),
      'endpoint' => $this->getSubmittedRefreshSetting($form_state, 'endpoint'),
      'secret' => $this->getRefreshSecret($form_state),
      'basic_auth' => $this->getSubmittedRefreshBasicAuth($form_state),
    ]);
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
    return $status;
  }

  /**
   * Submit handler for checking the refresh webhook connection.
   */
  public function checkRefreshConnection(array &$form, FormStateInterface $form_state): void {
    $endpoint = $this->getRuntimeRefreshSetting('endpoint');
    $secret = $this->getRuntimeRefreshSetting('secret');
    $options = $this->refreshClient->buildRequestOptions($this->getRuntimeRefreshSetting('basic_auth'));
    $options['http_errors'] = FALSE;
    if (!$endpoint || !$secret) {
      $this->messenger()->addError($this->t('Enter a refresh endpoint and refresh secret before checking the connection.'));
      $form_state->setRebuild();
      return;
    }

    try {
      $response = $this->refreshClient->post($endpoint, $secret, $this->refreshClient->buildPingPayload(), $options);
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
    $refresh_notifications = $form_state->getValue('refresh_notifications') ?: [];
    $value = $refresh_notifications[$key] ?? NULL;
    return $key === 'enabled' ? (bool) $value : ($value ?: NULL);
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
    $value = $this->config($this->entity->getConfigDependencyName())->get('refresh_notifications.' . $key);
    return $key === 'enabled' ? (bool) $value : ($value ?: NULL);
  }

  /**
   * Get submitted basic auth settings for refresh webhook requests.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The submitted basic auth settings.
   */
  private function getSubmittedRefreshBasicAuth(FormStateInterface $form_state): array {
    $basic_auth = $this->getSubmittedRefreshSetting($form_state, 'basic_auth') ?: [];
    if (empty($basic_auth['user']) && empty($basic_auth['pass'])) {
      return [];
    }
    return [
      'user' => $basic_auth['user'] ?? '',
      'pass' => $basic_auth['pass'] ?? '',
    ];
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
    return $this->getSubmittedRefreshSetting($form_state, 'secret')
      ?: $this->entity->getRefreshSecret()
      ?: $this->getRuntimeRefreshSetting('secret');
  }

}
