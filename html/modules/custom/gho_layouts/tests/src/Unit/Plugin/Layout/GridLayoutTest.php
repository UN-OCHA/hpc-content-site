<?php

namespace Drupal\Tests\gho_layouts\Unit\Plugin\Layout;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\gho_layouts\Plugin\Layout\GridLayout;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the configurable GHO grid layout plugin.
 */
#[Group('gho_layouts')]
class GridLayoutTest extends UnitTestCase {

  /**
   * Tests generating regions from the configured grid size.
   */
  public function testGenerateRegions(): void {
    $layout = $this->createLayout(['grid_size' => 3]);

    $this->assertSame([
      'region-1',
      'region-2',
      'region-3',
    ], array_keys($layout->generateRegions()));
    $this->assertSame('Region <em class="placeholder">1</em>', (string) $layout->generateRegions()['region-1']['label']);
  }

  /**
   * Tests build output keeps configured regions and definition attributes.
   */
  public function testBuild(): void {
    $layout = $this->createLayout(['grid_size' => 2]);

    $build = $layout->build([
      'region-1' => ['#markup' => 'One'],
      'region-2' => ['#markup' => 'Two'],
      'extra' => ['#markup' => 'Ignored'],
    ]);

    $this->assertArrayHasKey('region-1', $build);
    $this->assertArrayHasKey('region-2', $build);
    $this->assertArrayNotHasKey('extra', $build);
    $this->assertSame(['layout-grid'], $build['#attributes']['class']);
  }

  /**
   * Tests configuration form and submit handling for the grid size.
   */
  public function testConfigurationFormAndSubmit(): void {
    $layout = $this->createLayout(['grid_size' => 2]);

    $form = $layout->buildConfigurationForm([], new FormState());

    $this->assertSame('textfield', $form['grid_size']['#type']);
    $this->assertSame(2, $form['grid_size']['#default_value']);
    $this->assertSame(1, $form['grid_size']['#min']);

    $form_state = (new FormState())->setValues([
      'label' => 'Updated',
      'grid_size' => 4,
    ]);
    $layout->submitConfigurationForm($form, $form_state);

    $this->assertSame(4, $layout->getConfiguration()['grid_size']);
    $this->assertSame([
      'region-1',
      'region-2',
      'region-3',
      'region-4',
    ], array_keys($layout->getPluginDefinition()->getRegions()));
  }

  /**
   * Creates a grid layout with translated labels available.
   */
  private function createLayout(array $configuration): GridLayout {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $definition = new LayoutDefinition([
      'theme_hook' => 'layout__grid',
      'regions' => [],
      'attributes' => [
        'class' => ['layout-grid'],
      ],
    ]);
    $layout = new GridLayout(['label' => 'Grid'] + $configuration, 'layout_grid', $definition);
    return $layout;
  }

}
