<?php

namespace Drupal\Tests\ncms_ui\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Story;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the story entity.
 *
 * @group ncms_ui
 */
class StoryTest extends KernelTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'user',
    'node',
    'text',
    'system',
    'views',
    'ncms_publisher',
    'ncms_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig(['field', 'system', 'node']);
  }

  /**
   * Test the overview url.
   */
  public function testGetOverviewUrl() {
    $story = Story::create([
      'title' => 'Story title',
    ]);
    $this->assertEquals('/admin/content/stories', $story->getOverviewUrl()->toString());
  }

  /**
   * Test the bundle label.
   */
  public function testGetBundleLabel() {
    $this->createContentType([
      'type' => 'story',
      'name' => 'Story',
    ]);
    $story = Story::create();
    $this->assertEquals('Story', $story->getBundleLabel());
  }

}
