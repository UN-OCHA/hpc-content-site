<?php

namespace Drupal\Tests\ncms_ui\Unit\Routing;

use Drupal\ncms_ui\Routing\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the NCMS UI route subscriber.
 */
#[Group('ncms_ui')]
class RouteSubscriberTest extends UnitTestCase {

  /**
   * Tests route alterations for content and media admin workflows.
   */
  public function testAlterRoutes(): void {
    $collection = new RouteCollection();
    foreach ($this->routeNames() as $route_name) {
      $collection->add($route_name, new Route('/' . str_replace('.', '/', $route_name)));
    }
    $collection->get('entity.media.collection')->setRequirements([
      '_access' => 'TRUE',
      '_permission' => 'old permission',
    ]);

    $this->createRouteSubscriber()->alterRoutesForTest($collection);

    $this->assertSame(
      '\Drupal\ncms_ui\Controller\ViewController::nodeCanonicalRouteAccess',
      $collection->get('entity.node.canonical')->getRequirement('_custom_access')
    );
    $this->assertSame(
      '\Drupal\ncms_ui\Controller\TermController::addFormTitle',
      $collection->get('entity.taxonomy_term.add_form')->getDefault('_title_callback')
    );
    $this->assertSame(
      '\Drupal\ncms_ui\Controller\ContentController::versionAccess',
      $collection->get('entity.node.version_history')->getRequirement('_custom_access')
    );

    foreach (['node.add', 'node.add_page'] as $route_name) {
      $this->assertSame(
        '\Drupal\ncms_ui\Controller\ContentController::nodeCreateAccess',
        $collection->get($route_name)->getRequirement('_custom_access')
      );
    }
    foreach (['entity.media.add_form', 'entity.media.add_page'] as $route_name) {
      $this->assertSame(
        '\Drupal\ncms_ui\Controller\MediaController::mediaCreateAccess',
        $collection->get($route_name)->getRequirement('_custom_access')
      );
    }
    $this->assertSame([
      '_access' => 'TRUE',
      '_permission' => 'access media overview',
    ], $collection->get('entity.media.collection')->getRequirements());
  }

  /**
   * Tests that route alterations tolerate missing optional routes.
   */
  public function testAlterRoutesIgnoresMissingRoutes(): void {
    $collection = new RouteCollection();

    $this->createRouteSubscriber()->alterRoutesForTest($collection);

    $this->assertCount(0, $collection);
  }

  /**
   * Creates a test wrapper for the protected alterRoutes() hook.
   */
  private function createRouteSubscriber(): object {
    return new class() extends RouteSubscriber {

      /**
       * Exposes the protected route alteration hook for unit testing.
       */
      public function alterRoutesForTest(RouteCollection $collection): void {
        $this->alterRoutes($collection);
      }

    };
  }

  /**
   * Lists routes altered by the subscriber.
   *
   * @return string[]
   *   Route names.
   */
  private function routeNames(): array {
    return [
      'entity.node.canonical',
      'entity.taxonomy_term.add_form',
      'entity.node.version_history',
      'node.add',
      'node.add_page',
      'entity.media.add_form',
      'entity.media.add_page',
      'entity.media.collection',
    ];
  }

}
