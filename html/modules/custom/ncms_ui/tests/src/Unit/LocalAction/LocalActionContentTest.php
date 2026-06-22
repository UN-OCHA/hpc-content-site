<?php

namespace Drupal\Tests\ncms_ui\Unit\LocalAction;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_ui\LocalAction\LocalActionContent;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the content local action plugin.
 */
#[Group('ncms_ui')]
class LocalActionContentTest extends UnitTestCase {

  /**
   * Tests route details and the bundle-specific title.
   */
  public function testContentLocalAction(): void {
    $node_type = $this->createMock(EntityInterface::class);
    $node_type->method('label')->willReturn('Article');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with('article')->willReturn($node_type);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('node_type')->willReturn($storage);

    $action = new LocalActionContent([], 'node.add.article', [
      'title' => 'Add content',
    ], $this->createMock(RouteProviderInterface::class));
    $action->setStringTranslation($this->createTranslation());
    $this->setProtectedProperty($action, 'entityTypeManager', $entity_type_manager);

    $this->assertSame('node.add', $action->getRouteName());
    $this->assertSame('Add article', (string) $action->getTitle());
    $this->assertSame([
      'node_type' => 'article',
    ], $action->getRouteParameters($this->createMock(RouteMatchInterface::class)));
  }

  /**
   * Creates a translation mock that returns untranslated strings.
   */
  private function createTranslation(): TranslationInterface {
    $translation = $this->createMock(TranslationInterface::class);
    $translation->method('translateString')
      ->willReturnCallback(fn($string) => $string->getUntranslatedString());
    return $translation;
  }

  /**
   * Sets a protected property on an object.
   */
  private function setProtectedProperty(object $object, string $property, $value): void {
    $reflection = new \ReflectionProperty($object, $property);
    $reflection->setAccessible(TRUE);
    $reflection->setValue($object, $value);
  }

}
