<?php

namespace Drupal\Tests\ncms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Story;

/**
 * Tests the story entity.
 *
 * @group ncms_ui
 */
class StoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'user',
    'node',
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
    $this->installConfig('field');
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

}
