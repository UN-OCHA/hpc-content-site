<?php

namespace Drupal\Tests\ncms_ui\Unit\LocalAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_ui\LocalAction\LocalActionTaxonomy;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the taxonomy local action plugin.
 */
#[Group('ncms_ui')]
class LocalActionTaxonomyTest extends UnitTestCase {

  /**
   * Tests the vocabulary-specific title.
   */
  public function testVocabularyLocalActionTitle(): void {
    $vocabulary = $this->createMock(EntityInterface::class);
    $vocabulary->method('label')->willReturn('Theme');
    $request = new Request();
    $request->attributes->set('taxonomy_vocabulary', $vocabulary);

    $action = new LocalActionTaxonomy([], 'taxonomy.add', [
      'title' => 'Add term',
    ], $this->createMock(RouteProviderInterface::class));
    $action->setStringTranslation($this->createTranslation());

    $this->assertSame('Add theme', (string) $action->getTitle($request));
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

}
