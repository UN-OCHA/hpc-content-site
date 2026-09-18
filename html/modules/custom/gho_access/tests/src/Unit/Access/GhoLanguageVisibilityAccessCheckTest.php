<?php

namespace Drupal\Tests\gho_access\Unit\Access;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\gho_access\Access\GhoLanguageVisibilityAccessCheck;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO language visibility route access check.
 */
#[Group('gho_access')]
class GhoLanguageVisibilityAccessCheckTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    require_once $this->root . '/modules/custom/gho_access/gho_access.module';
  }

  /**
   * Tests that unrelated route node parameters are left to normal node access.
   */
  public function testAllowsWhenRouteNodeDoesNotMatch(): void {
    $checker = new GhoLanguageVisibilityAccessCheck($this->mockRouteMatch(42));
    $node = $this->mockNode(99, 'en');

    $access = $checker->access($this->mockAccountWithLanguageBypassPermission(FALSE), $node);

    $this->assertTrue($access->isAllowed());
  }

  /**
   * Tests translated route nodes are allowed after inheriting cacheability.
   */
  public function testAllowsTranslatedRouteNode(): void {
    $this->setCurrentLanguage('en');
    $checker = new GhoLanguageVisibilityAccessCheck($this->mockRouteMatch(99));
    $node = $this->mockNode(99, 'en');

    $access = $checker->access($this->mockAccountWithLanguageBypassPermission(TRUE), $node);

    $this->assertTrue($access->isAllowed());
    $this->assertContains('user.permissions', $access->getCacheContexts());
    $this->assertContains('node:99', $access->getCacheTags());
  }

  /**
   * Tests untranslated route nodes are converted from forbidden to 404.
   */
  public function testThrowsNotFoundForUntranslatedRouteNode(): void {
    $this->setCurrentLanguage('en');
    $checker = new GhoLanguageVisibilityAccessCheck($this->mockRouteMatch(99));

    $this->expectException(CacheableNotFoundHttpException::class);

    $checker->access($this->mockAccountWithLanguageBypassPermission(FALSE), $this->mockNode(99, 'fr'));
  }

  /**
   * Mocks the current route match raw node parameter.
   */
  private function mockRouteMatch(?int $node_id): RouteMatchInterface {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRawParameter')->with('node')->willReturn($node_id);
    return $route_match;
  }

  /**
   * Mocks an account with optional language bypass permission.
   */
  private function mockAccountWithLanguageBypassPermission(bool $can_view_untranslated): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->willReturnCallback(static fn (string $permission) => $permission === 'view untranslated content' && $can_view_untranslated);
    return $account;
  }

  /**
   * Mocks the node ID, language, and cacheability used by the access result.
   */
  private function mockNode(int $id, string $langcode): NodeInterface {
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn($langcode);

    $node = $this->createMock(NodeInterface::class);
    $node->method('id')->willReturn($id);
    $node->method('language')->willReturn($language);
    $node->method('getCacheContexts')->willReturn([]);
    $node->method('getCacheTags')->willReturn(['node:' . $id]);
    $node->method('getCacheMaxAge')->willReturn(Cache::PERMANENT);
    return $node;
  }

  /**
   * Registers the language manager used by gho_access_check_language_access().
   */
  private function setCurrentLanguage(string $langcode): void {
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn($langcode);

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager->method('getCurrentLanguage')->willReturn($language);

    $cache_contexts_manager = $this->createMock(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);

    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    $container->set('language_manager', $language_manager);
    \Drupal::setContainer($container);
  }

}
