<?php

namespace Drupal\Tests\ncms_ui\Traits;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Common helper trait for functional and webdriver tests.
 */
trait ContentTestTrait {

  use EntityReferenceTestTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setupContent();
  }

  /**
   * Setup the content types.
   */
  protected function setupContent() {
    // Create major tags vocabulary and fields.
    Vocabulary::create([
      'vid' => 'major_tags',
      'name' => 'Major tags',
    ])->save();

    // Create content space vocabulary and fields.
    Vocabulary::create([
      'vid' => 'content_space',
      'name' => 'Content space',
    ])->save();
    $handler_settings = [
      'target_bundles' => [
        'tags' => 'major_tags',
      ],
    ];
    $this->createEntityReferenceField('taxonomy_term', 'content_space', 'field_major_tags', 'Tags', 'taxonomy_term', 'default', $handler_settings);

    $handler_settings = [
      'target_bundles' => [
        'team' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('node', 'article', 'field_content_space', 'Content space', 'taxonomy_term', 'default', $handler_settings);
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_content_space', [
        'type' => 'options_select',
        'region' => 'content',
      ])
      ->save();

    $handler_settings = [
      'target_bundles' => [
        'team' => 'content_space',
      ],
    ];
    $this->createEntityReferenceField('user', 'user', 'field_content_spaces', 'Content spaces', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Make content be replicatable.
    $this->config('replicate_ui.settings')
      ->set('entity_types', ['node'])
      ->save();
    \Drupal::service('router.builder')->rebuild();
    Cache::invalidateTags(['entity_types']);

    node_access_rebuild(TRUE);
  }

  /**
   * Create a major tag term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term object.
   */
  protected function createMajorTag() {
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'major_tags',
    ]);
    $term->save();
    return $term;
  }

  /**
   * Create a content space term.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The created term object.
   */
  protected function createContentSpace() {
    $tag = $this->createMajorTag();
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'vid' => 'content_space',
      'field_major_tags' => ['target_id' => $tag->id()],
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
   * @param int $status
   *   The published status of the article.
   *
   * @return \Drupal\node\NodeInterface
   *   The created node object.
   */
  protected function createArticleInContentSpace($title, $content_space_id, $status = NodeInterface::PUBLISHED) {
    $node = Node::create([
      'type' => 'article',
      'title' => $title,
      'field_content_space' => ['target_id' => $content_space_id],
      'status' => $status,
      'moderation_state' => [
        'value' => $status == NodeInterface::PUBLISHED ? 'published' : 'draft',
      ],
    ]);
    $result = $node->save();
    $this->assertEquals($result, SAVED_NEW);
    return $node;
  }

  /**
   * Create a user with permissions and associated content spaces.
   *
   * @param array $content_spaces
   *   An array of content space term objects.
   *
   * @return \Drupal\user\Entity\User|false
   *   The user object or FALSE.
   */
  protected function createEditorUserWithContentSpaces(array $content_spaces) {
    return $this->drupalCreateUser([
      'access content overview',
      'access administration pages',
      'view the administration theme',
      'create article content',
      'edit own article content',
      'revert article revisions',
      'replicate entities',
      'use article_workflow transition create_new_draft',
      'use article_workflow transition delete',
      'use article_workflow transition publish',
      'use article_workflow transition restore_draft',
      'use article_workflow transition restore_publish',
      'use article_workflow transition save_draft_leave_current_published',
      'use article_workflow transition update',
    ], NULL, NULL, [
      'field_content_spaces' => array_map(function ($content_space) {
        return ['target_id' => $content_space->id()];
      }, $content_spaces),
    ]);
  }

  /**
   * Set the content space for the current user session.
   *
   * @param \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace $content_space
   *   The content space to activate.
   */
  protected function setContentSpace($content_space) {
    /** @var \Drupal\ncms_ui\ContentSpaceManager $content_manager */
    $content_manager = $this->container->get('ncms_ui.content_space.manager');
    $content_manager->setCurrentContentSpaceId($content_space->id());

    /** @var \Drupal\Core\Cache\CacheBackendInterface $render_cache */
    $render_cache = $this->container->get('cache.render');
    $render_cache->invalidateAll();
  }

  /**
   * Get the currently active content space for the current user session.
   *
   * @return \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace
   *   The content space to activate.
   */
  protected function getContentSpace() {
    /** @var \Drupal\ncms_ui\ContentSpaceManager $content_manager */
    $content_manager = $this->container->get('ncms_ui.content_space.manager');
    return $content_manager->getCurrentContentSpace();
  }

}
