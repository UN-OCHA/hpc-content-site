<?php

namespace Drupal\Tests\ncms_ui\Functional;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
class ContentSpaceAccessTest extends ContentTestBase {

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

    // Create a user with permission to manage content from content space 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([$content_space_1]));

    $this->drupalGet('/admin/content');
    $this->assertSession()->pageTextContains($node_1_1->label());
    $this->assertSession()->pageTextContains($node_2_1->label());
    $this->assertSession()->pageTextContains($node_3_1->label());
    $this->assertSession()->pageTextNotContains($node_1_2->label());
    $this->assertSession()->pageTextNotContains($node_2_2->label());
    $this->assertSession()->pageTextNotContains($node_3_2->label());

    // Create a user with permission to manage content from content space 2.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([$content_space_2]));

    $this->drupalGet('/admin/content');
    $this->assertSession()->pageTextNotContains($node_1_1->label());
    $this->assertSession()->pageTextNotContains($node_2_1->label());
    $this->assertSession()->pageTextNotContains($node_3_1->label());
    $this->assertSession()->pageTextContains($node_1_2->label());
    $this->assertSession()->pageTextContains($node_2_2->label());
    $this->assertSession()->pageTextContains($node_3_2->label());
  }

  /**
   * Tests revision access.
   */
  public function testNodeRevisionAccess() {
    $content_space_1 = $this->createContentSpace();
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());

    $content_space_2 = $this->createContentSpace();
    $node_1_2 = $this->createArticleInContentSpace('Article 1 for Content space 2', $content_space_2->id());

    // Create a user with permission to manage content from content space 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([$content_space_1]));

    // Check that editable nodes have the expected tabs and that a link to the
    // version history is there.
    $this->drupalGet($node_1_1->toUrl('edit-form'));
    $this->assertSession()->pageTextContains($node_1_1->label());
    $this->assertSession()->elementExists('css', '.block-local-tasks-block a[data-drupal-link-system-path="node/' . $node_1_1->id() . '/edit"]');
    $this->assertSession()->elementExists('css', '.block-local-tasks-block a[data-drupal-link-system-path="node/' . $node_1_1->id() . '/revisions"]');
    $this->assertSession()->linkExists('Versions');

    $this->drupalGet($node_1_1->toUrl('version-history'));
    $this->assertSession()->pageTextContains($node_1_1->label());

    // Check that the version history of non-editable nodes can't be accessed.
    $this->drupalGet($node_1_2->toUrl('version-history'));
    $this->assertSession()->pageTextContains('Access denied');
  }

  /**
   * Tests that nodes can be edited by users in the same content space.
   */
  public function testNodeEditAccessByContentSpace() {
    $content_space_1 = $this->createContentSpace();
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $node_2_1 = $this->createArticleInContentSpace('Article 2 for Content space 1', $content_space_1->id());
    $node_3_1 = $this->createArticleInContentSpace('Article 3 for Content space 1', $content_space_1->id());

    $content_space_2 = $this->createContentSpace();
    $node_1_2 = $this->createArticleInContentSpace('Article 1 for Content space 2', $content_space_2->id());
    $node_2_2 = $this->createArticleInContentSpace('Article 2 for Content space 2', $content_space_2->id());
    $node_3_2 = $this->createArticleInContentSpace('Article 3 for Content space 2', $content_space_2->id());

    // Create a user with permission to manage content from content space 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([$content_space_1]));
    $this->assertEquals($content_space_1->id(), $this->getContentSpace()->id());

    $this->drupalGet('/node/' . $node_1_1->id() . '/edit');
    $this->assertSession()->pageTextContains($node_1_1->label());

    $this->drupalGet('/node/' . $node_2_1->id() . '/edit');
    $this->assertSession()->pageTextContains($node_2_1->label());

    $this->drupalGet('/node/' . $node_3_1->id() . '/edit');
    $this->assertSession()->pageTextContains($node_3_1->label());

    $this->drupalGet('/node/' . $node_1_2->id() . '/edit');
    $this->assertSession()->pageTextContains('Access denied');

    $this->drupalGet('/node/' . $node_2_2->id() . '/edit');
    $this->assertSession()->pageTextContains('Access denied');

    $this->drupalGet('/node/' . $node_3_2->id() . '/edit');
    $this->assertSession()->pageTextContains('Access denied');
  }

  /**
   * Tests content space switching.
   *
   * It would be preferrable to test the actual frontend functionality using
   * the content space selector from ContentSpaceSelectForm. Unfortunately,
   * that form is added to the Gin sidebar in ncms_ui.module and it's not easy
   * to mock that here. So the less preferrable way to test this is to manually
   * do what ContentSpaceSelectForm::buildForm does when the selector is used:
   * Set the new content space and clear the render cache.
   */
  public function testContentSpaceSwitching() {
    $content_space_1 = $this->createContentSpace();
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $node_2_1 = $this->createArticleInContentSpace('Article 2 for Content space 1', $content_space_1->id());
    $node_3_1 = $this->createArticleInContentSpace('Article 3 for Content space 1', $content_space_1->id());

    $content_space_2 = $this->createContentSpace();
    $node_1_2 = $this->createArticleInContentSpace('Article 1 for Content space 2', $content_space_2->id());
    $node_2_2 = $this->createArticleInContentSpace('Article 2 for Content space 2', $content_space_2->id());
    $node_3_2 = $this->createArticleInContentSpace('Article 3 for Content space 2', $content_space_2->id());

    // Create a user with permission to manage content from content space 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([$content_space_1]));
    $this->assertEquals($content_space_1->id(), $this->getContentSpace()->id());

    // Confirm we see what we should.
    $this->drupalGet('/admin/content');
    $this->assertSession()->pageTextContains($node_1_1->label());
    $this->assertSession()->pageTextContains($node_2_1->label());
    $this->assertSession()->pageTextContains($node_3_1->label());
    $this->assertSession()->pageTextNotContains($node_1_2->label());
    $this->assertSession()->pageTextNotContains($node_2_2->label());
    $this->assertSession()->pageTextNotContains($node_3_2->label());
    // Edit links.
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_1_1->id() . '/edit?destination=/admin/content"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_2_1->id() . '/edit?destination=/admin/content"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_3_1->id() . '/edit?destination=/admin/content"]');
    // Replicate links.
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_1_1->id() . '/replicate?destination"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_2_1->id() . '/replicate?destination"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_3_1->id() . '/replicate?destination"]');
    // Add content link.
    $this->assertSession()->elementExists('css', 'a[href="/node/add/article"]');

    // Set the content space 2 as the active one.
    $this->setContentSpace($content_space_2);
    $this->assertEquals($content_space_2->id(), $this->getContentSpace()->id());

    // Confirm we see what we should.
    $this->drupalGet('/admin/content');
    $this->assertSession()->pageTextNotContains($node_1_1->label());
    $this->assertSession()->pageTextNotContains($node_2_1->label());
    $this->assertSession()->pageTextNotContains($node_3_1->label());
    $this->assertSession()->pageTextContains($node_1_2->label());
    $this->assertSession()->pageTextContains($node_2_2->label());
    $this->assertSession()->pageTextContains($node_3_2->label());
    // Edit links.
    $this->assertSession()->elementNotExists('css', '.dropbutton a[href="/node/' . $node_1_2->id() . '/edit?destination=/admin/content"]');
    $this->assertSession()->elementNotExists('css', '.dropbutton a[href="/node/' . $node_2_2->id() . '/edit?destination=/admin/content"]');
    $this->assertSession()->elementNotExists('css', '.dropbutton a[href="/node/' . $node_3_2->id() . '/edit?destination=/admin/content"]');
    // Replicate links.
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_1_2->id() . '/replicate?destination"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_2_2->id() . '/replicate?destination"]');
    $this->assertSession()->elementExists('css', '.dropbutton a[href="/node/' . $node_3_2->id() . '/replicate?destination"]');
    // Add content link.
    $this->assertSession()->elementNotExists('css', 'a[href="/node/add/article"]');
  }

}
