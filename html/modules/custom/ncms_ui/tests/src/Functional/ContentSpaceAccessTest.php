<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
class ContentSpaceAccessTest extends BrowserTestBase {

  use EntityReferenceTestTrait;
  use TaxonomyTestTrait;

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
  protected function setUp(): void {
    parent::setUp();

    $this->setupContent();
  }

  /**
   * Tests that nodes show up only in their respective content space.
   */
  public function testNodesVisibleForCurrentContentSpace() {
    $content_space_1 = $this->createContentSpace();
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $node_2_1 = $this->createArticleInContentSpace('Article 2 for Content space 1', $content_space_1->id());
    $node_3_1 = $this->createArticleInContentSpace('Article 3 for Content space 1', $content_space_1->id());

    $content_space_2 = $this->createContentSpace();
    $node_1_2 = $this->createArticleInContentSpace('Article 1 for Content space 2', $content_space_2->id());
    $node_2_2 = $this->createArticleInContentSpace('Article 2 for Content space 2', $content_space_2->id());
    $node_3_2 = $this->createArticleInContentSpace('Article 3 for Content space 2', $content_space_2->id());

    // Create a user with permission to view the actions administration pages.
    $this->drupalLogin($this->drupalCreateUser([
      'administer nodes',
      'access administration pages',
      'view the administration theme',
    ], NULL, NULL, [
      'field_content_spaces' => [
        'target_id' => $content_space_1->id(),
      ],
    ]));

    $this->drupalGet('/admin/articles');
    $this->assertSession()->pageTextContains($node_1_1->label());
    $this->assertSession()->pageTextContains($node_2_1->label());
    $this->assertSession()->pageTextContains($node_3_1->label());
    $this->assertSession()->pageTextNotContains($node_1_2->label());
    $this->assertSession()->pageTextNotContains($node_2_2->label());
    $this->assertSession()->pageTextNotContains($node_3_2->label());

    // Create a user with permission to view the actions administration pages.
    $this->drupalLogin($this->drupalCreateUser([
      'administer nodes',
      'access administration pages',
      'view the administration theme',
    ], NULL, NULL, [
      'field_content_spaces' => [
        'target_id' => $content_space_2->id(),
      ],
    ]));

    $this->drupalGet('/admin/articles');
    $this->assertSession()->pageTextNotContains($node_1_1->label());
    $this->assertSession()->pageTextNotContains($node_2_1->label());
    $this->assertSession()->pageTextNotContains($node_3_1->label());
    $this->assertSession()->pageTextContains($node_1_2->label());
    $this->assertSession()->pageTextContains($node_2_2->label());
    $this->assertSession()->pageTextContains($node_3_2->label());
  }

  /**
   * Create a content space term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term object.
   */
  private function createContentSpace() {
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'content_space',
    ]);
    $term->save();
    return $term;
  }

  /**
   * Create an article in the given content space.
   *
   * @param string $title
   *   The title of the node.
   * @param int $content_space_id
   *   The id of the content space.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node object.
   */
  private function createArticleInContentSpace($title, $content_space_id) {
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'field_content_space' => ['target_id' => $content_space_id],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $result = $node->save();
    $this->assertEquals($result, SAVED_NEW);
    return $node;
  }

  /**
   * Setup content types and content for these tests.
   */
  private function setupContent() {
    // Create content space vocabulary and fields.
    Vocabulary::create([
      'vid' => 'content_space',
      'name' => 'Content space',
    ])->save();
    $handler_settings = [
      'target_bundles' => [
        'team' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_content_space', 'Content space', 'taxonomy_term', 'default', $handler_settings);

    $handler_settings = [
      'target_bundles' => [
        'team' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('user', 'user', 'field_content_spaces', 'Content spaces', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    node_access_rebuild(TRUE);
  }

}
