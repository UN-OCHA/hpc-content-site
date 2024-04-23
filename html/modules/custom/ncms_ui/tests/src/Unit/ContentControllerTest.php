<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ncms_ui\ContentSpaceManager;
use Drupal\ncms_ui\Controller\ContentController;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the ContentController.
 */
class ContentControllerTest extends UnitTestCase {

  /**
   * The media controller.
   *
   * @var \Drupal\ncms_ui\Controller\ContentController
   */
  private $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $content_space_manager = $this->prophesize(ContentSpaceManager::class);
    $this->controller = new ContentController($content_space_manager->reveal());

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
    $controller = ContentController::create($container);
    \Drupal::setContainer($container);
    $this->assertInstanceOf(ContentController::class, $controller);
  }

  /**
   * Test the mediaCreateAccess method.
   */
  public function testNodeCreateAccess() {
    $content_space_manager = $this->prophesize(ContentSpaceManager::class);
    $account = $this->prophesize(AccountInterface::class)->reveal();

    $content_space_manager->userIsInValidContentSpace($account)->willReturn(TRUE);
    $this->controller->setContentSpaceManager($content_space_manager->reveal());
    $this->assertEquals(TRUE, $this->controller->nodeCreateAccess($account)->isAllowed());

    $content_space_manager->userIsInValidContentSpace($account)->willReturn(FALSE);
    $this->controller->setContentSpaceManager($content_space_manager->reveal());
    $this->assertEquals(FALSE, $this->controller->nodeCreateAccess($account)->isAllowed());
  }

  /**
   * Test the versionAccess method.
   */
  public function testVersionAccess() {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account)->willReturn(TRUE);
    $node->access('view revision', $account)->willReturn(TRUE);
    $this->assertEquals(TRUE, $this->controller->versionAccess($node->reveal(), $account)->isAllowed());

    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account)->willReturn(TRUE);
    $node->access('view revision', $account)->willReturn(FALSE);
    $this->assertEquals(FALSE, $this->controller->versionAccess($node->reveal(), $account)->isAllowed());

    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account)->willReturn(FALSE);
    $node->access('view revision', $account)->willReturn(TRUE);
    $this->assertEquals(FALSE, $this->controller->versionAccess($node->reveal(), $account)->isAllowed());

    $node = $this->prophesize(NodeInterface::class);
    $node->access('update', $account)->willReturn(FALSE);
    $node->access('view revision', $account)->willReturn(FALSE);
    $this->assertEquals(FALSE, $this->controller->versionAccess($node->reveal(), $account)->isAllowed());
  }

}
