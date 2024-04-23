<?php

namespace Drupal\Tests\ncms_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ncms_ui\Entity\Content\Document;

/**
 * Tests the document entity.
 *
 * @group ncms_ui
 */
class DocumentTest extends KernelTestBase {

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
    $document = Document::create([
      'title' => 'Document title',
    ]);
    $this->assertEquals('/admin/content/documents', $document->getOverviewUrl()->toString());
  }

}
