<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;

/**
 * Tests logic around content editing.
 *
 * @group ncms_ui
 */
class ContentEditTest extends ContentTestBase {

  /**
   * Tests new articles can be added.
   */
  public function testContentAdd() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));

    $add_url = '/node/add/article';
    $view_url = '/node/1';
    $edit_url = '/node/1/edit';
    $this->drupalGet($add_url);

    $this->getSession()->getPage()->fillField('edit-title-0-value', 'Test article');
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content');
    $this->getSession()->getPage()->pressButton('Save as draft');

    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('Saved a new draft version of Test article.');

    $this->drupalGet($view_url);
    $assert_session->pageTextContains('Test article');
    $assert_session->pageTextContains('Test content');

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');
  }

  /**
   * Tests that the content editing works.
   *
   * Checks creating revisions and saving drafts.
   */
  public function testContentEdit() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentBase::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    $assert_session = $this->assertSession();

    // Go to the edit form of that node.
    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');

    // Click Save as draft without any changes.
    $this->getSession()->getPage()->pressButton('Save as draft');
    $assert_session->pageTextContains('No changes detected for Article 1 for Content space 1. The content has not been updated.');
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');

    // Click Save as draft with changes.
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content draft');
    $this->getSession()->getPage()->pressButton('Save as draft');
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');

    // Publish the version without any changes and go back to the edit page.
    $this->getSession()->getPage()->pressButton('Save and publish');
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Published');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');
  }

}
