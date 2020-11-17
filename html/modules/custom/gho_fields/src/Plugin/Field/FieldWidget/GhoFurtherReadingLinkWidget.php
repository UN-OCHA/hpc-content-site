<?php

namespace Drupal\gho_fields\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'gho_further_reading_link' widget.
 *
 * @FieldWidget(
 *   id = "gho_further_reading_link",
 *   label = @Translation("GHO further reading link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class GhoFurtherReadingLinkWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items[$delta];

    // Get the base selector for the URI field.
    $parents = $element['#field_parents'];
    $parents[] = $this->fieldDefinition->getName();
    $selector = array_shift($parents);
    if (!empty($parents)) {
      $selector .= '[' . implode('][', $parents) . ']';
    }
    $selector .= '[' . $delta . '][uri]';

    // Retrieve the default source value which is stored as an attribute.
    $options = $item->get('options')->getValue();
    $attributes = $options['attributes'] ?? [];
    $source = $attributes['source'] ?? NULL;

    $element['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Author / Source'),
      '#default_value' => $source,
      // Mark the source as required when the URI is filled.
      '#states' => [
        'required' => [
          ':input[name="' . $selector . '"]' => ['filled' => TRUE],
        ],
      ],
    ];

    // Add a callback to validate the source field ensuring it's not empty
    // when the URI field is filled.
    $element['#element_validate'][] = [
      get_called_class(),
      'validateSourceElement',
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    foreach ($values as $delta => $value) {
      if (isset($value['source'])) {
        $values[$delta]['options']['attributes']['source'] = $value['source'];
        unset($values[$delta]['source']);
      }
    }
    return $values;
  }

  /**
   * Form element validation handler for the 'source' element.
   *
   * Display an error if the source field is empty while the URI is filled.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   Form.
   */
  public static function validateSourceElement(array &$element, FormStateInterface $form_state, array $form) {
    if ($element['uri']['#value'] !== '' && $element['source']['#value'] === '') {
      $error = t('The @source field is required if there is @uri input.', [
        '@source' => $element['source']['#title'],
        '@uri' => $element['uri']['#title'],
      ]);
      $form_state->setError($element['source'], $error);
    }
  }

}
