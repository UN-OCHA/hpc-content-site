<?php

namespace Drupal\Tests\ncms_ui\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
class DocumentEditTest extends ContentTestBaseJavascript {

  use ContentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'entity_browser',
    'entity_browser_table',
    'field',
    'field_ui',
    'ncms_ui',
    'ncms_ui_test',
    'node',
    'paragraphs',
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
  protected $defaultTheme = 'common_design_subtheme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setupContentSpaceStructure();

    // Create document content type with paragraph field.
    $this->addParagraphedContentType('document');

    $handler_settings = [
      'target_bundles' => [
        'team' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('node', 'document', 'field_content_space', 'Content space', 'taxonomy_term', 'default', $handler_settings);
    EntityFormDisplay::load('node.document.default')
      ->setComponent('field_content_space', [
        'type' => 'options_select',
        'region' => 'content',
      ])
      ->save();

    $this->addParagraphsType('document_chapter');
    $settings = [
      'handler' => 'default:node',
      'handler_settings' => [
        'target_bundles' => [
          'article' => 'article',
        ],
      ],
    ];
    $this->addFieldtoParagraphType('document_chapter', 'field_articles', 'entity_reference', $settings);
    $settings = [
      'open' => TRUE,
      'entity_browser' => 'articles',
      'field_widget_display' => 'label',
      'field_widget_edit' => '1',
      'field_widget_remove' => '1',
      'selection_mode' => 'selection_append',
      'additional_fields' => [
        'options' => [
          'status' => 'status',
        ],
      ],
      'field_widget_replace' => 0,
      'field_widget_display_settings' => [],
    ];
    $this->setParagraphsWidgetSettings('document_chapter', 'field_articles', $settings, 'entity_browser_entity_reference', 'paragraph');
  }

  /**
   * Test document creation in the frontend.
   */
  public function testDocumentCreate() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces(
      [$content_space_1],
      [
        'create document content',
        'edit own document content',
      ]));

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentInterface::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $node_1_1 */

    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Go to the document add form.
    $this->drupalGet('/node/add/document');
    $assert_session->buttonExists('Add more articles');
    $page->pressButton('Add more articles');
    $this->waitForAjaxToFinish();
    $this->htmlOutput(NULL);

    $assert_session->elementExists('css', 'iframe.entity-browser-modal-iframe');
  }

}
