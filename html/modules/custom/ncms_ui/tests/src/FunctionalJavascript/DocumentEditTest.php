<?php

namespace Drupal\Tests\ncms_ui\FunctionalJavascript;

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

    $this->setupDocumentStructure();

    $this->setupContentSpaceStructure();
    $this->addContentSpaceFieldToBundle('document');
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
