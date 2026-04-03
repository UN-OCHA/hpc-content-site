<?php

namespace Drupal\gho_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gho_fields\Plugin\Field\FieldFormatter\GhoInteractiveContentFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'gho_interactive_content' widget.
 *
 * @FieldWidget(
 *   id = "gho_interactive_content",
 *   label = @Translation("GHO interactive content embed code"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class GhoInteractiveContentWidget extends StringTextareaWidget {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    // Add a callback to validate the embed code.
    $element['#element_validate'][] = [
      get_called_class(),
      'validateEmbedCode',
    ];
    $element['#access'] = $this->account->hasPermission('add interactive content embed code');
    return $element;
  }

  /**
   * Form element validation handler to validate the embed code.
   *
   * Display an error if the embed code doesn't have the required attributes.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   Form.
   */
  public static function validateEmbedCode(array &$element, FormStateInterface $form_state, array $form) {
    if ($element['value']['#value'] !== '') {
      $attributes = GhoInteractiveContentFormatter::extractAttributes($element['value']['#value']);
      if (is_null($attributes)) {
        $error = t("Invalid embed code in the @field field. It must be a Datawrapper or Power BI iframe.", [
          '@field' => $element['value']['#title'],
        ]);
        $form_state->setError($element['value'], $error);
      }
      else {
        $missing = GhoInteractiveContentFormatter::validateMandatoryAttributes($attributes);
        foreach ($missing as $attribute) {
          $error = t("The iframe in the @field field doesn't have a valid @attribute attribute.", [
            '@field' => $element['value']['#title'],
            '@attribute' => $attribute,
          ]);
          $form_state->setError($element['value'], $error);
        }
      }
    }
  }

}
