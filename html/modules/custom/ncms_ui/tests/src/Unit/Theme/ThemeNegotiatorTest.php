<?php

namespace Drupal\Tests\ncms_ui\Unit\Theme;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Theme\ThemeNegotiator;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the NCMS theme negotiator.
 */
class ThemeNegotiatorTest extends UnitTestCase {

  /**
   * Tests that ordinary admin routes use this negotiator.
   */
  public function testAppliesToOrdinaryAdminRoutes(): void {
    $negotiator = $this->createNegotiator();

    $this->assertTrue($negotiator->applies($this->mockRouteMatch('entity.node.edit_form')));
  }

  /**
   * Tests that accessible frontend preview routes can use the default theme.
   */
  public function testDoesNotApplyToAccessiblePreviewRoutes(): void {
    $negotiator = $this->createNegotiator();

    $this->assertFalse($negotiator->applies($this->mockRouteMatch('entity.node.preview')));

    $node = $this->mockArticleWithAccess(TRUE);
    $this->assertFalse($negotiator->applies($this->mockRouteMatch('entity.node.preview', $node)));
  }

  /**
   * Tests that inaccessible preview routes fall back to the admin theme.
   */
  public function testAppliesToInaccessiblePreviewRoutes(): void {
    $negotiator = $this->createNegotiator();
    $node = $this->mockArticleWithAccess(FALSE);

    $this->assertTrue($negotiator->applies($this->mockRouteMatch('entity.node.preview', $node)));
  }

  /**
   * Tests that the configured admin theme is selected when applicable.
   */
  public function testDetermineActiveTheme(): void {
    $config = $this->createMock(Config::class);
    $config->method('get')->with('admin')->willReturn('gin');

    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')->with('system.theme')->willReturn($config);

    $negotiator = $this->createNegotiator($config_factory);

    $this->assertSame('gin', $negotiator->determineActiveTheme($this->mockRouteMatch('entity.node.edit_form')));
  }

  /**
   * Creates the theme negotiator under test.
   */
  private function createNegotiator(?ConfigFactoryInterface $config_factory = NULL): ThemeNegotiator {
    return new ThemeNegotiator(
      $config_factory ?? $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(AdminContext::class)
    );
  }

  /**
   * Mocks a route match.
   */
  private function mockRouteMatch(string $route_name, ?NodeInterface $node = NULL): RouteMatchInterface {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $route_match->method('getRouteName')->willReturn($route_name);
    $route_match->method('getParameter')->with('node')->willReturn($node);
    return $route_match;
  }

  /**
   * Mocks an article so access() keeps Drupal's default arguments.
   */
  private function mockArticleWithAccess(bool $access): NodeInterface {
    $node = $this->getMockBuilder(Article::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['access'])
      ->getMock();
    $node->method('access')->willReturn($access);
    return $node;
  }

}
