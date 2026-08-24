<?php

namespace Drupal\Tests\gho_general\Unit\StringTranslation;

use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Routing\AdminContext;
use Drupal\gho_general\StringTranslation\GhoTranslationManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO translation manager.
 */
#[Group('gho_general')]
class GhoTranslationManagerTest extends UnitTestCase {

  /**
   * Tests that admin routes use the original string.
   */
  public function testAdminRoutesUseOriginalString(): void {
    $admin_context = $this->createMock(AdminContext::class);
    $admin_context->method('isAdminRoute')->willReturn(TRUE);

    $manager = new GhoTranslationManager($this->createDefaultLanguage(), $admin_context);

    $this->assertSame('Original string', $manager->getStringTranslation('fr', 'Original string', ''));
  }

  /**
   * Creates the default language service for the translation manager.
   */
  private function createDefaultLanguage(): LanguageDefault {
    return new LanguageDefault([
      'id' => 'en',
    ]);
  }

}
