<?php

namespace Drupal\Tests\ncms_ui\FunctionalJavascript;

use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Tests logic around content editing.
 *
 * @group ncms_ui
 */
class ContentEditModalsTest extends ContentTestBaseJavascript {

  const CONFIRM_SAVE_AND_PUBLISH = 'This will make this article publicly available on the API and will automatically create a page for this article on Humanitarian Action. Are you sure?';
  const CONFIRM_PUBLISH_CORRECTION = 'This will publish these changes as a correction to the currently published version, which will be entirely replaced. Are you sure?';
  const CONFIRM_PUBLISH_REVISION = 'This will publish these changes as a new revision to the currently published version, which will remain publicly available as an earlier or original version. Are you sure?';
  const CONFIRM_NO_CHANGES = 'No changes have been made to the already published version. Please make some changes before publishing again.';

  /**
   * Tests that the correct node versions show up in the content overview.
   */
  public function testContentEditModals() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentInterface::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
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
    $assert_session->waitForText('No changes detected for Article 1 for Content space 1. The article has not been updated.');

    // Reopen edit and assert state.
    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');

    // Click Save as draft with changes.
    $body_element = $assert_session->waitForElement('css', '#edit-body-0-value + .ck-editor .ck-editor__editable');
    $body_element->setValue('Test content draft');
    $this->getSession()->getPage()->pressButton('Save as draft');
    $assert_session->waitForText('Saved a new draft version of Article 1 for Content space 1.');

    // Reopen edit and assert state.
    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Draft');
    $assert_session->elementTextContains('css', '.ck-editor', 'Test content draft');
    $assert_session->buttonExists('Save and publish');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Publish as correction');
    $assert_session->buttonNotExists('Publish as revision');

    // Test the modal confirmation dialogs by publish the version without any
    // changes.
    $this->getSession()->getPage()->pressButton('Save and publish');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_SAVE_AND_PUBLISH);

    // Cancel the dialog and confirm nothing changed.
    $this->pressModalButton('Cancel');
    $this->waitForAjaxToFinish();
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Draft');

    // Now click save again to open the dialog again and confirm this time.
    $this->getSession()->getPage()->pressButton('Save and publish');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_SAVE_AND_PUBLISH);

    // Confirm the dialog and go back to the edit page.
    $this->pressModalButton('Ok');
    $assert_session->waitForText('Published current version of Article 1 for Content space 1');
  }

  /**
   * Tests publishing as a correction.
   */
  public function testContentEditPublishAsCorrectionModals() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $this->assertInstanceOf(ContentInterface::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');

    // Make a change.
    $body_element = $assert_session->waitForElement('css', '#edit-body-0-value + .ck-editor .ck-editor__editable');
    $body_element->setValue('Test content draft with correction');

    // Click publish as correction with changes.
    $this->getSession()->getPage()->pressButton('Publish as correction');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_PUBLISH_CORRECTION);

    // Cancel the dialog and confirm nothing changed.
    $this->pressModalButton('Cancel');
    $this->waitForAjaxToFinish();
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');

    // Now click save again to open the dialog again and confirm this time.
    $this->getSession()->getPage()->pressButton('Publish as correction');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_PUBLISH_CORRECTION);

    // Confirm the dialog and go back to the edit page.
    $this->pressModalButton('Ok');
    $assert_session->waitForText('Created and published a new version of Article 1 for Content space 1. Unpublished the last published version.');

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Published');
    $assert_session->elementTextContains('css', '.ck-editor', 'Test content draft with correction');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');
  }

  /**
   * Tests publishing as a revision.
   */
  public function testContentEditPublishAsRevisionModals() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $this->assertInstanceOf(ContentInterface::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');

    // Make a change.
    $body_element = $assert_session->waitForElement('css', '#edit-body-0-value + .ck-editor .ck-editor__editable');
    $body_element->setValue('Test content draft with correction');

    // Click publish as revision with changes.
    $this->getSession()->getPage()->pressButton('Publish as revision');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_PUBLISH_REVISION);

    // Cancel the dialog and confirm nothing changed.
    $this->pressModalButton('Cancel');
    $this->waitForAjaxToFinish();
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');

    // Now click save again to open the dialog again and confirm this time.
    $this->getSession()->getPage()->pressButton('Publish as revision');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_PUBLISH_REVISION);

    // Confirm the dialog and go back to the edit page.
    $this->pressModalButton('Ok');
    $assert_session->waitForText('Created and published a new version of Article 1 for Content space 1');

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#2 Published');
    $assert_session->elementTextContains('css', '.ck-editor', 'Test content draft with correction');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');
  }

  /**
   * Tests modal messages when re-publishing an article without changes.
   */
  public function testContentEditNoChangesModals() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $this->assertInstanceOf(ContentInterface::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\ContentInterface $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    /** @var \Drupal\FunctionalJavascriptTests\WebDriverWebAssert $assert_session */
    $assert_session = $this->assertSession();

    $this->drupalGet($edit_url);
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');
    $assert_session->buttonExists('Publish as correction');
    $assert_session->buttonExists('Publish as revision');
    $assert_session->buttonExists('Save as draft');
    $assert_session->buttonExists('Preview');
    $assert_session->buttonNotExists('Save and publish');

    // Click publish as correction without changes.
    $this->getSession()->getPage()->pressButton('Publish as correction');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_NO_CHANGES);

    // Confirm the dialog and confirm nothing changed.
    $this->pressModalButton('Ok');
    $this->waitForAjaxToFinish();
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');

    // Do the same with publish as revision.
    $this->getSession()->getPage()->pressButton('Publish as revision');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '#drupal-modal', self::CONFIRM_NO_CHANGES);

    // Cancel the dialog and confirm nothing changed.
    $this->pressModalButton('Ok');
    $this->waitForAjaxToFinish();
    $assert_session->elementTextContains('css', '#edit-meta-published', '#1 Published');
  }

}
