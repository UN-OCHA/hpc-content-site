<?php

namespace Drupal\Tests\ncms_ui\Unit\Autocomplete;

use Drupal\ncms_ui\Autocomplete\AutocompleteRouteSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the autocomplete route subscriber.
 */
#[Group('ncms_ui')]
class AutocompleteRouteSubscriberTest extends UnitTestCase {

  /**
   * Tests that the core entity autocomplete controller is replaced.
   */
  public function testAlterRoutesReplacesAutocompleteController(): void {
    $collection = new RouteCollection();
    $collection->add('system.entity_autocomplete', new Route('/entity-autocomplete'));

    (new AutocompleteRouteSubscriber())->alterRoutes($collection);

    $this->assertSame(
      '\Drupal\ncms_ui\Autocomplete\EntityAutocompleteController::handleAutocomplete',
      $collection->get('system.entity_autocomplete')->getDefault('_controller')
    );
  }

  /**
   * Tests that missing autocomplete routes are ignored.
   */
  public function testAlterRoutesIgnoresMissingAutocompleteRoute(): void {
    $collection = new RouteCollection();

    (new AutocompleteRouteSubscriber())->alterRoutes($collection);

    $this->assertCount(0, $collection);
  }

}
