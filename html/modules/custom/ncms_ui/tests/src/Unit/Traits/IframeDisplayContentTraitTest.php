<?php

namespace Drupal\Tests\ncms_ui\Unit\Traits;

use Drupal\ncms_ui\Entity\Content\Article;
use Drupal\ncms_ui\Traits\IframeDisplayContentTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests iframe URL generation for content entities.
 */
class IframeDisplayContentTraitTest extends UnitTestCase {

  /**
   * Tests preview iframe URL generation.
   */
  public function testGetIframePreviewUrl(): void {
    $entity = $this->mockIframeArticle();
    $url = $entity->getIframePreviewUrl(['absolute' => TRUE]);

    $this->assertSame('entity.node.preview', $url->getRouteName());
    $this->assertSame([
      'node_preview' => 'test-uuid',
      'view_mode_id' => 'full',
    ], $url->getRouteParameters());
    $this->assertTrue($url->getOption('absolute'));
  }

  /**
   * Tests standalone iframe URL generation.
   */
  public function testGetIframeStandaloneUrl(): void {
    $entity = $this->mockIframeArticle();
    $url = $entity->getIframeStandaloneUrl();

    $this->assertSame('entity.node.standalone', $url->getRouteName());
    $this->assertSame(['node' => 123], $url->getRouteParameters());
  }

  /**
   * Tests standalone revision iframe URL generation.
   */
  public function testGetIframeStandaloneRevisionUrl(): void {
    $entity = $this->mockIframeArticle();
    $url = $entity->getIframeStandaloneRevisionUrl();

    $this->assertSame('entity.node_revision.standalone', $url->getRouteName());
    $this->assertSame([
      'node' => 123,
      'node_revision' => 456,
    ], $url->getRouteParameters());
  }

  /**
   * Tests that the trait refuses non-node consumers.
   */
  public function testTraitRequiresNodeEntity(): void {
    // The anonymous class uses the trait outside NodeInterface to exercise the
    // guard shared by all iframe URL helper methods.
    $entity = new class() {
      use IframeDisplayContentTrait;
    };

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The IframeDisplayContentTrait can only be used by node entities');

    $entity->getIframeStandaloneUrl();
  }

  /**
   * Mocks the article identifiers used by the iframe URL helpers.
   */
  private function mockIframeArticle(): Article {
    $entity = $this->getMockBuilder(Article::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['id', 'uuid', 'getRevisionId'])
      ->getMock();
    $entity->method('id')->willReturn(123);
    $entity->method('uuid')->willReturn('test-uuid');
    $entity->method('getRevisionId')->willReturn(456);
    return $entity;
  }

}
