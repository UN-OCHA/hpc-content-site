<?php

namespace Drupal\ncms_tags\Plugin\Validation\Constraint;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "UniqueTermName",
 *   label = @Translation("Unique term name", context = "Validation"),
 *   type = { "string" }
 * )
 */
#[Constraint(
  id: 'UniqueTermName',
  label: new TranslatableMarkup('Unique term name', [], ['context' => 'Validation']),
  type: ['string']
)]
class UniqueTermName extends Constraint implements ConstraintValidatorInterface {

  /**
   * A context object.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($item_list, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $item_list */
    $entity = $item_list->getEntity();
    $field_name = $item_list->getFieldDefinition()->getName();
    if ($entity && !empty($item_list->value)) {
      $value = $item_list->value;
      $properties = [
        'vid' => $entity->bundle(),
        $field_name => $value,
      ];
      $entities = $this->getEntityTypeManager()->getStorage($entity->getEntityTypeId())->loadByProperties($properties);
      if (count($entities) && $entity) {
        // Filter out the entity that this field belongs to.
        $entities = array_filter($entities, function (FieldableEntityInterface $_entity) use ($entity) {
          return $_entity->id() != $entity->id();
        });
      }
      if (count($entities)) {
        $arguments = [
          '%value' => $value,
        ];
        $this->context->addViolation('%value is already in use. Please choose a different value.', $arguments);
      }
    }
  }

  /**
   * Get the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public static function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

}
