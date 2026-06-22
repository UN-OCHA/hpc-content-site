<?php

namespace Drupal\Tests\ncms_tags\Unit\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ncms_tags\Plugin\Validation\Constraint\UniqueTermName;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the unique term name constraint.
 */
#[Group('ncms_tags')]
class UniqueTermNameTest extends UnitTestCase {

  /**
   * Tests that duplicate terms in the same vocabulary are rejected.
   */
  public function testValidateRejectsDuplicateTermName(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('id')->willReturn(10);
    $entity->method('bundle')->willReturn('theme');
    $entity->method('getEntityTypeId')->willReturn('taxonomy_term');

    $duplicate = $this->createMock(FieldableEntityInterface::class);
    $duplicate->method('id')->willReturn(20);

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('name');

    $item_list = $this->createMock(FieldItemListInterface::class);
    $item_list->method('getEntity')->willReturn($entity);
    $item_list->method('getFieldDefinition')->willReturn($field_definition);
    // The constraint checks empty($item_list->value), which calls __isset()
    // before __get() on Drupal field item lists.
    $item_list->method('__isset')->with('value')->willReturn(TRUE);
    $item_list->method('__get')->with('value')->willReturn('Shelter');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'vid' => 'theme',
        'name' => 'Shelter',
      ])
      ->willReturn([$duplicate]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('taxonomy_term')->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->once())
      ->method('addViolation')
      ->with('%value is already in use. Please choose a different value.', [
        '%value' => 'Shelter',
      ]);

    $constraint = new UniqueTermName();
    $constraint->initialize($context);
    $constraint->validate($item_list, $constraint);
  }

  /**
   * Tests that the current entity is ignored when validating unchanged names.
   */
  public function testValidateIgnoresCurrentTerm(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('id')->willReturn(10);
    $entity->method('bundle')->willReturn('theme');
    $entity->method('getEntityTypeId')->willReturn('taxonomy_term');

    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('name');

    $item_list = $this->createMock(FieldItemListInterface::class);
    $item_list->method('getEntity')->willReturn($entity);
    $item_list->method('getFieldDefinition')->willReturn($field_definition);
    // The constraint checks empty($item_list->value), which calls __isset()
    // before __get() on Drupal field item lists.
    $item_list->method('__isset')->with('value')->willReturn(TRUE);
    $item_list->method('__get')->with('value')->willReturn('Shelter');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$entity]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('taxonomy_term')->willReturn($storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())->method('addViolation');

    $constraint = new UniqueTermName();
    $constraint->initialize($context);
    $constraint->validate($item_list, $constraint);
  }

}
