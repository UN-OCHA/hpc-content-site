<?php

namespace Drupal\Tests\gho_layouts\Unit;

use Drupal\Core\Form\FormState;
use Drupal\gho_layouts\LayoutSelectHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the layout select helper.
 */
#[Group('gho_layouts')]
class LayoutSelectHelperTest extends UnitTestCase {

  /**
   * Tests common layout select alterations without region restrictions.
   */
  public function testProcessLayoutSelectWithoutRestrictions(): void {
    $element = ['#options' => ['layout_one' => 'One']];
    $form = [
      'layout_paragraphs' => [
        'config' => [
          'label' => ['#access' => TRUE],
        ],
      ],
    ];

    $processed = LayoutSelectHelper::processLayoutSelect($element, new FormState(), $form);

    $this->assertSame(['gho_layouts/layout_select'], $processed['#attached']['library']);
    $this->assertSame('container', $form['layout_paragraphs']['config']['#type']);
    $this->assertFalse($form['layout_paragraphs']['config']['label']['#access']);
    $this->assertArrayNotHasKey('disabled_message', $form['layout_paragraphs']);
  }

}
