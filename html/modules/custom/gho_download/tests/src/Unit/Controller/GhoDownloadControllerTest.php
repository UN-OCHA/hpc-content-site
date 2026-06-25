<?php

namespace Drupal\Tests\gho_download\Unit\Controller;

use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Drupal\gho_download\Controller\GhoDownloadController;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the GHO download controller.
 */
#[Group('gho_download')]
class GhoDownloadControllerTest extends UnitTestCase {

  /**
   * Tests that non-article nodes cannot be downloaded.
   */
  public function testDownloadRejectsNonArticleNodes(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page');

    $this->expectException(CacheableNotFoundHttpException::class);

    (new GhoDownloadController())->download($node);
  }

  /**
   * Tests that article nodes without a PDF field cannot be downloaded.
   */
  public function testDownloadRejectsArticlesWithoutPdfField(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('article');
    $node->method('hasField')->with('field_pdf')->willReturn(FALSE);

    $this->expectException(CacheableNotFoundHttpException::class);

    (new GhoDownloadController())->download($node);
  }

}
