<?php

namespace Drupal\Tests\ncms_ui;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\ncms_ui\Controller\ViewController;
use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\Content\Story;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the ViewController.
 */
class ViewControllerTest extends UnitTestCase {

  /**
   * The term controller.
   *
   * @var \Drupal\ncms_ui\Controller\ViewController
   */
  private $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $string_translation = $this->prophesize(TranslationInterface::class);
    $string_translation->translateString(Argument::cetera())->will(function ($args) {
      return $args[0]->getUntranslatedString();
    });

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->prophesize(EntityTypeManagerInterface::class)->reveal());
    $container->set('renderer', $this->prophesize(RendererInterface::class)->reveal());
    $container->set('current_user', $this->prophesize(AccountProxyInterface::class)->reveal());
    $container->set('entity.repository', $this->prophesize(EntityRepositoryInterface::class)->reveal());
    $container->set('url_generator', $this->prophesize(UrlGeneratorInterface::class)->reveal());
    $container->set('string_translation', $string_translation->reveal());
    \Drupal::setContainer($container);

    $this->controller = ViewController::create($container);
  }

  /**
   * Test the previewTitle method.
   */
  public function testPreviewTitle() {
    $article = $this->prophesize(Article::class);
    $article->label()->willReturn('Article title');
    $article->getContentStatus()->willReturn(ContentBase::CONTENT_STATUS_PUBLISHED);
    $this->assertEquals('Preview: Article title (Latest published)', (string) $this->controller->previewTitle($article->reveal()));
    $article->getContentStatus()->willReturn(ContentBase::CONTENT_STATUS_DRAFT);
    $this->assertEquals('Preview: Article title (Latest draft)', (string) $this->controller->previewTitle($article->reveal()));
    $article->getContentStatus()->willReturn(ContentBase::CONTENT_STATUS_PUBLISHED_WITH_DRAFT);
    $this->assertEquals('Preview: Article title (Latest draft)', (string) $this->controller->previewTitle($article->reveal()));

    $story = $this->prophesize(Story::class);
    $story->label()->willReturn('Story title');
    $this->assertEquals('Preview: Story title', (string) $this->controller->previewTitle($story->reveal()));
  }

  /**
   * Test the preview method.
   */
  public function testPreviewModal() {
    $url = $this->prophesize(Url::class);
    $url->toString()->willReturn('/some-url');

    $article = $this->prophesize(Article::class);
    $article->label()->willReturn('Article title');
    $article->getBundleLabel()->willReturn('Article');
    $article->getIframePreviewUrl()->willReturn($url->reveal());
    $article->getContentStatus()->willReturn(ContentBase::CONTENT_STATUS_PUBLISHED);

    $form_object = $this->prophesize(EntityFormInterface::class);
    $form_object->getEntity()->willReturn($article->reveal());

    $form_state = $this->prophesize(FormStateInterface::class);
    $form_state->getFormObject()->willReturn($form_object->reveal());

    $response = $this->controller->previewModal([], $form_state->reveal());
    $this->assertInstanceOf(AjaxResponse::class, $response);
  }

  /**
   * Test the viewIframe method.
   */
  public function testViewIframe() {
    $article = $this->prophesize(Article::class);
    $article->label()->willReturn('Article title');
    $article->getBundleLabel()->willReturn('Article');

    // Assert general structure.
    $url = $this->prophesize(Url::class);
    $url->toString()->willReturn('/some-url');
    $article->getIframeStandaloneUrl()->willReturn($url->reveal());
    $build = $this->controller->viewIframe($article->reveal());
    $this->assertIsArray($build);
    $this->assertEquals('/some-url', $build['iframe']['#attributes']['src']);

    // Assert the different types of iframe sources.
    $article->getIframeStandaloneUrl()
      ->willReturn($url->reveal())
      ->shouldBeCalled();
    $this->controller->viewIframe($article->reveal(), NULL, FALSE);

    $article->getIframeStandaloneRevisionUrl()
      ->willReturn($url->reveal())
      ->shouldBeCalled();
    $this->controller->viewIframe($article->reveal(), $article->reveal(), FALSE);

    $article->getIframePreviewUrl()
      ->willReturn($url->reveal())
      ->shouldBeCalled();
    $this->controller->viewIframe($article->reveal(), NULL, TRUE);
  }

  /**
   * Test the nodeCanonicalRouteAccess method.
   */
  public function testNodeCanonicalRouteAccess() {
    $account = $this->prophesize(AccountInterface::class);
    $account->isAuthenticated()->willReturn(FALSE);
    $article = $this->prophesize(Article::class);
    $article->access('update', $account->reveal())->willReturn(FALSE);
    $result = $this->controller->nodeCanonicalRouteAccess($article->reveal(), $account->reveal());
    $this->assertInstanceOf(AccessResultInterface::class, $result);
    $this->assertFalse($result->isAllowed());

    $account->isAuthenticated()->willReturn(TRUE);
    $article->access('update', $account->reveal())->willReturn(FALSE);
    $result = $this->controller->nodeCanonicalRouteAccess($article->reveal(), $account->reveal());
    $this->assertFalse($result->isAllowed());

    $article->access('update', $account->reveal())->willReturn(TRUE);
    $result = $this->controller->nodeCanonicalRouteAccess($article->reveal(), $account->reveal());
    $this->assertTrue($result->isAllowed());
  }

}
