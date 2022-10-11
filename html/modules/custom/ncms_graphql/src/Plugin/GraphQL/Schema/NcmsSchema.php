<?php

namespace Drupal\ncms_graphql\Plugin\GraphQL\Schema;

use Drupal\Core\Form\FormStateInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema;

/**
 * Defines a composable schema for the HPC Content Module.
 *
 * @Schema(
 *   id = "ncms_schema",
 *   name = "HPC Content Module Schema",
 *   extensions = "ncms_schema_extension",
 * )
 */
class NcmsSchema extends ComposableSchema {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['require_access_key'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require access key'),
      '#description' => $this->t('Whether requests for this server require the use of an access key.'),
      '#default_value' => $this->configuration['require_access_key'] ?? FALSE,
    ];

    $form['access_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Access key'),
      '#description' => $this->t('Set an access key that is used to grant access to the endpoint for this schema.'),
      '#default_value' => $this->configuration['access_key'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="schema_configuration[ncms_schema][require_access_key]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if (array_key_exists('access_key', $this->configuration) && !empty($this->configuration['access_key'])) {
      $form['access_key']['#description'] .= '<br />' . $this->t('<em>Note:</em> An access key is already set. You can set a new one, or leave this field empty to keep the current one.');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Get the submitted access key.
    $access_key = $form_state->getValue('access_key');

    /** @var \Drupal\graphql\Form\ServerForm $form_object */
    $form_object = $form_state->getFormObject();

    // Reload the server entity to get it's original configuration.
    $graphql_server = \Drupal::entityTypeManager()->getStorage('graphql_server')->load($form_object->getEntity()->id());
    $ncms_schema_configuration = $graphql_server ? $graphql_server->get('schema_configuration')['ncms_schema'] : [];
    if (empty($access_key) && !empty($ncms_schema_configuration['access_key'])) {
      // If no access key has been submitted, but one is currently set in the
      // configuration, make sure to keep that.
      $form_state->setValue('access_key', $ncms_schema_configuration['access_key']);
    }
    parent::validateConfigurationForm($form, $form_state);
  }

}
