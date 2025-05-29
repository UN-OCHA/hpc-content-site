<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\Core\Database\Database;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
abstract class ContentTestBase extends BrowserTestBase {

  use ContentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'ncms_ui',
    'ncms_ui_test',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * Using the standard profile as this has a lot of additional configuration.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setUp();

    // Create the content structure. The article content type is already
    // provided by the standard install profile, but we still need to create
    // the document type, the content spaces and some additional fields, e.g.
    // paragraphs.
    $this->setupDocumentStructure();
    $this->setupContentSpaceStructure();
    $this->addContentSpaceFieldToBundle('article');
    $this->addContentSpaceFieldToBundle('document');

  }

  /**
   * Assert a table entry in content_moderation_state_field_data.
   */
  protected function assertContentModerationTableEntry(NodeInterface $entity) {
    $result = Database::getConnection()->select('content_moderation_state_field_data')
      ->fields('content_moderation_state_field_data', [
        'id',
        'revision_id',
        'content_entity_id',
        'content_entity_revision_id',
      ])
      ->condition('content_entity_id', $entity->id())
      ->execute()
      ->fetchAll();
    $this->assertEquals(1, count($result), 'Table content_moderation_state_field_data contains a single record for the entity.');
  }

}
