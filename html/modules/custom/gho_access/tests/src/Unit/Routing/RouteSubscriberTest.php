<?php

namespace Drupal\Tests\gho_access\Unit\Routing;

use Drupal\gho_access\Routing\RouteSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the GHO access route subscriber.
 */
#[Group('gho_access')]
class RouteSubscriberTest extends UnitTestCase {

  /**
   * Tests node canonical and admin content route alterations.
   */
  public function testAlterRoutes(): void {
    $collection = new RouteCollection();
    $collection->add('entity.node.canonical', new Route('/node/{node}', [], [
      '_entity_access' => 'node.view',
    ]));
    $collection->add('view.content.page', new Route('/admin/content'));
    $collection->add('view.files.page', new Route('/admin/files'));

    $this->createRouteSubscriber()->alterRoutesForTest($collection);

    $this->assertSame([
      '_access_check_gho_language_visibility' => '{node}',
      '_entity_access' => 'node.view',
    ], $collection->get('entity.node.canonical')->getRequirements());
    $this->assertTrue($collection->get('view.content.page')->getOption('_admin_route'));
    $this->assertNull($collection->get('view.files.page')->getOption('_admin_route'));
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

}
