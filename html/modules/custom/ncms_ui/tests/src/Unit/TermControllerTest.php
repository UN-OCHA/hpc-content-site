<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_ui\Controller\TermController;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the TermController.
 */
class TermControllerTest extends UnitTestCase {

  /**
   * The term controller.
   *
   * @var \Drupal\ncms_ui\Controller\TermController
   */
  private $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->controller = new TermController();

    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });

    $this->controller->setStringTranslation($string_translation->reveal());
  }

  /**
   * Test the addFormTitle method.
   */
  public function testAddFormTitle() {
    $vocabulary = $this->prophesize(VocabularyInterface::class);
    $vocabulary->label()->willReturn('Vocabulary label');
    $this->assertEquals('Add vocabulary label', (string) $this->controller->addFormTitle($vocabulary->reveal()));
  }

}
