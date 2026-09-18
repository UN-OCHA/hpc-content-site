<?php

namespace Drupal\Tests\ncms_paragraphs\Unit\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\ncms_paragraphs\Plugin\paragraphs\Behavior\PromoteBehavior;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the promotable paragraph behavior plugin.
 */
#[Group('ncms_paragraphs')]
class PromoteBehaviorTest extends UnitTestCase {

  /**
   * Tests promoted paragraphs get the expected class and library.
   */
  public function testViewAddsPromotedAssets(): void {
    $behavior = $this->createBehavior();
    $paragraph = $this->mockParagraphBehaviorSetting(TRUE);
    $build = [];

    $behavior->view($build, $paragraph, $this->createMock(EntityViewDisplayInterface::class), 'default');

    $this->assertSame(['gho-paragraph-promoted'], $build['#attributes']['class']);
    $this->assertSame(['common_design_subtheme/gho-promoted-paragraph'], $build['#attached']['library']);
  }

  /**
   * Tests ordinary paragraphs are not changed by the view callback.
   */
  public function testViewLeavesUnpromotedParagraphsUnchanged(): void {
    $behavior = $this->createBehavior();
    $paragraph = $this->mockParagraphBehaviorSetting(FALSE);
    $build = [];

    $behavior->view($build, $paragraph, $this->createMock(EntityViewDisplayInterface::class), 'default');

    $this->assertSame([], $build);
  }

  /**
   * Tests the behavior form uses the stored promoted setting.
   */
  public function testBuildBehaviorForm(): void {
    $behavior = $this->createBehavior();
    $behavior->setStringTranslation($this->getStringTranslationStub());

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->with('promoted_behavior', 'promoted', FALSE)
      ->willReturn(TRUE);

    $form = [];
    $form = $behavior->buildBehaviorForm($paragraph, $form, new FormState());

    $this->assertSame('checkbox', $form['promoted']['#type']);
    $this->assertSame('Promoted', (string) $form['promoted']['#title']);
    $this->assertTrue($form['promoted']['#default_value']);
  }

  /**
   * Creates the paragraph behavior under test.
   */
  private function createBehavior(): PromoteBehavior {
    return new PromoteBehavior(
      [],
      'promoted_behavior',
      [],
      $this->createMock(EntityFieldManagerInterface::class)
    );
  }

  /**
   * Mocks the concrete Paragraph type required by the view() signature.
   */
  private function mockParagraphBehaviorSetting(bool $promoted): Paragraph {
    $paragraph = $this->getMockBuilder(Paragraph::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['getBehaviorSetting'])
      ->getMock();
    $paragraph->method('getBehaviorSetting')
      ->with('promoted_behavior', 'promoted', FALSE)
      ->willReturn($promoted);
    return $paragraph;
  }

}
