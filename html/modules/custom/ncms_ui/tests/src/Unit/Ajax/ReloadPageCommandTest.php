<?php

namespace Drupal\Tests\ncms_ui\Unit\Ajax;

use Drupal\ncms_ui\Ajax\ReloadPageCommand;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the reload page Ajax command.
 */
#[Group('ncms_ui')]
class ReloadPageCommandTest extends UnitTestCase {

  /**
   * Tests the rendered command and attached library.
   */
  public function testRenderAndAttachedAssets(): void {
    $command = new ReloadPageCommand();

    $this->assertSame([
      'command' => 'reloadPage',
    ], $command->render());
    $this->assertSame([
      'ncms_ui/ajax_commands',
    ], $command->getAttachedAssets()->getLibraries());
  }

}
