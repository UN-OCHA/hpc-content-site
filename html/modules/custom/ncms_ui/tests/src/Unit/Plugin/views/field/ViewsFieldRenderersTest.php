<?php

namespace Drupal\Tests\ncms_ui\Unit\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\ncms_ui\Entity\Content\Document;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Plugin\views\field\ArticleCountField;
use Drupal\ncms_ui\Plugin\views\field\ContentStatusField;
use Drupal\ncms_ui\Plugin\views\field\LatestPublishedVersionField;
use Drupal\ncms_ui\Plugin\views\field\LatestVersionField;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ResultRow;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests small NCMS UI Views field renderers.
 */
#[Group('ncms_ui')]
class ViewsFieldRenderersTest extends UnitTestCase {

  /**
   * Tests content status marker rendering.
   */
  public function testContentStatusFieldRender(): void {
    $entity = $this->createMock(ContentInterface::class);
    $entity->method('getContentStatusLabel')->willReturn('Published');
    $entity->method('isPublished')->willReturn(TRUE);

    $field = new ContentStatusField([], 'content_status_field', []);
    $build = $field->render(new ResultRow(['_entity' => $entity]));

    $this->assertSame('html_tag', $build['#type']);
    $this->assertSame('span', $build['#tag']);
    $this->assertSame('Published', $build['#value']);
    $this->assertSame(['marker', 'marker--published'], $build['#attributes']['class']);
    $this->assertNull($field->render(new ResultRow(['_entity' => $this->createMock(EntityInterface::class)])));
  }

  /**
   * Tests latest published revision rendering.
   */
  public function testLatestPublishedVersionFieldRender(): void {
    $revision = $this->mockContentVersion(123, 456, 7, 'Published', TRUE);
    $row = new ResultRow(['latest_published_version' => $revision]);

    $field = new LatestPublishedVersionField([], 'latest_published_version_field', []);
    $build = $field->render($row);

    $this->assertRevisionLinkBuild($build, 123, 456, '#7 (Published)', TRUE);
    $this->assertNull($field->render(new ResultRow()));
  }

  /**
   * Tests latest revision rendering.
   */
  public function testLatestVersionFieldRender(): void {
    $revision = $this->mockContentVersion(123, 789, 9, 'Draft', FALSE);
    $row = new ResultRow(['latest_version' => $revision]);

    $field = new LatestVersionField([], 'latest_version_field', []);
    $build = $field->render($row);

    $this->assertRevisionLinkBuild($build, 123, 789, '#9 (Draft)', FALSE);
  }

  /**
   * Tests article count rendering as text and link.
   */
  public function testArticleCountFieldRender(): void {
    $document = $this->mockDocument(321);

    $field = new ArticleCountField([], 'article_count_field', []);
    $field->field_alias = 'article_count';
    $field->options = ['link_to_article_list' => FALSE];

    $build = $field->render(new ResultRow([
      '_entity' => $document,
      'article_count' => 5,
    ]));
    $this->assertSame('html_tag', $build['#type']);
    $this->assertSame('span', $build['#tag']);
    $this->assertSame(5, $build['#value']);

    $field->options = ['link_to_article_list' => TRUE];
    $build = $field->render(new ResultRow([
      '_entity' => $document,
      'article_count' => 5,
    ]));
    $this->assertSame('link', $build['#type']);
    $this->assertSame(5, $build['#title']);
    $this->assertSame('view.content.page_articles', $build['#url']->getRouteName());
    $this->assertSame(['document' => 321], $build['#url']->getOption('query'));

    $this->assertNull($field->render(new ResultRow(['_entity' => $document])));
    $this->assertNull($field->render(new ResultRow([
      '_entity' => $this->createMock(EntityInterface::class),
      'article_count' => 5,
    ])));
  }

  /**
   * Asserts the common revision link render array.
   */
  private function assertRevisionLinkBuild(array $build, int $node_id, int $revision_id, string $title, bool $published): void {
    $this->assertSame('link', $build['#type']);
    $this->assertSame('entity.node.revision', $build['#url']->getRouteName());
    $this->assertSame([
      'node' => $node_id,
      'node_revision' => $revision_id,
    ], $build['#url']->getRouteParameters());
    $this->assertSame('span', $build['#title']['#tag']);
    $this->assertSame($title, (string) $build['#title']['#value']);
    $expected_classes = $published ? ['marker', 'marker--published'] : ['marker'];
    $this->assertSame($expected_classes, $build['#title']['#attributes']['class']);
  }

  /**
   * Mocks a content revision used by latest-version renderers.
   */
  private function mockContentVersion(int $id, int $revision_id, int $version_id, string $status_label, bool $published): ContentVersionInterface {
    $revision = $this->createMock(ContentVersionInterface::class);
    $revision->method('id')->willReturn($id);
    $revision->method('getRevisionId')->willReturn($revision_id);
    $revision->method('getVersionId')->willReturn($version_id);
    $revision->method('getVersionStatusLabel')->willReturn($status_label);
    $revision->method('isPublished')->willReturn($published);
    return $revision;
  }

  /**
   * Mocks a document entity for the article count field.
   */
  private function mockDocument(int $id): Document {
    $document = $this->getMockBuilder(Document::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['id'])
      ->getMock();
    $document->method('id')->willReturn($id);
    return $document;
  }

}
