<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Controller\MediaController;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the MediaController.
 */
class MediaControllerTest extends UnitTestCase {

  /**
   * The media controller.
   *
   * @var \Drupal\ncms_ui\Controller\MediaController
   */
  private $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $content_space_manager = $this->prophesize(ContentSpaceManager::class);
    $this->controller = new MediaController($content_space_manager->reveal());

    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });

    $this->controller->setStringTranslation($string_translation->reveal());
  }

  /**
   * Test the controller instantiation using ::create().
   */
  public function testCreateClass() {
    $container = new ContainerBuilder();
    $container->set('ncms_ui.content_space.manager', $this->prophesize(ContentSpaceManager::class)->reveal());
    $controller = MediaController::create($container);
    \Drupal::setContainer($container);
    $this->assertInstanceOf(MediaController::class, $controller);
  }

  /**
   * Test the mediaCreateAccess method.
   */
  public function testMediaCreateAccess() {
    $content_space_manager = $this->prophesize(ContentSpaceManager::class);
    $account = $this->prophesize(AccountInterface::class);

    $content_space_manager->userIsInValidContentSpace($account->reveal())->willReturn(TRUE);
    $this->controller->setContentSpaceManager($content_space_manager->reveal());
    $this->assertEquals(TRUE, $this->controller->mediaCreateAccess($account->reveal())->isAllowed());

    $content_space_manager->userIsInValidContentSpace($account->reveal())->willReturn(FALSE);
    $this->controller->setContentSpaceManager($content_space_manager->reveal());
    $this->assertEquals(FALSE, $this->controller->mediaCreateAccess($account->reveal())->isAllowed());
  }

}
