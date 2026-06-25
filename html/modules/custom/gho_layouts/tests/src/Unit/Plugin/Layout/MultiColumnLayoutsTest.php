<?php

namespace Drupal\Tests\gho_layouts\Unit\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefinition;
use Drupal\gho_layouts\Plugin\Layout\FourColumnInteractiveContentLayout;
use Drupal\gho_layouts\Plugin\Layout\ThreeColumnInteractiveContentLayout;
use Drupal\gho_layouts\Plugin\Layout\TwoColumnInteractiveContentLayout;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the configurable GHO multi-column layouts.
 */
#[Group('gho_layouts')]
class MultiColumnLayoutsTest extends UnitTestCase {

  /**
   * Tests default column width configuration.
   */
  public function testDefaultConfiguration(): void {
    $this->assertSame('50-50', $this->createTwoColumnLayout()->defaultConfiguration()['column_widths']);
    $this->assertSame('33-34-33', $this->createThreeColumnLayout()->defaultConfiguration()['column_widths']);
    $this->assertSame('25-25-25-25', $this->createFourColumnLayout()->defaultConfiguration()['column_widths']);
  }

  /**
   * Tests the selected column width is reflected in build classes.
   */
  public function testBuildAddsColumnWidthClasses(): void {
    $layout = $this->createTwoColumnLayout(['column_widths' => '33-67']);

    $build = $layout->build([
      'first' => ['#markup' => 'First'],
      'second' => ['#markup' => 'Second'],
    ]);

    $this->assertSame([
      'layout',
      'layout--twocol',
      'layout--twocol--33-67',
    ], $build['#attributes']['class']);
  }

  /**
   * Creates the two-column layout.
   */
  private function createTwoColumnLayout(array $configuration = []): TwoColumnInteractiveContentLayout {
    return new TwoColumnInteractiveContentLayout(
      ['label' => 'Two columns'] + $configuration,
      'layout_twocol',
      $this->createDefinition('layout--twocol')
    );
  }

  /**
   * Creates the three-column layout.
   */
  private function createThreeColumnLayout(): ThreeColumnInteractiveContentLayout {
    return new ThreeColumnInteractiveContentLayout(
      ['label' => 'Three columns'],
      'layout_threecol',
      $this->createDefinition('layout--threecol')
    );
  }

  /**
   * Creates the four-column layout.
   */
  private function createFourColumnLayout(): FourColumnInteractiveContentLayout {
    return new FourColumnInteractiveContentLayout(
      ['label' => 'Four columns'],
      'layout_fourcol',
      $this->createDefinition('layout--fourcol')
    );
  }

  /**
   * Creates a layout definition with two generic regions.
   */
  private function createDefinition(string $template): LayoutDefinition {
    return new LayoutDefinition([
      'theme_hook' => 'layout',
      'template' => $template,
      'regions' => [
        'first' => ['label' => 'First'],
        'second' => ['label' => 'Second'],
      ],
    ]);
  }

}
