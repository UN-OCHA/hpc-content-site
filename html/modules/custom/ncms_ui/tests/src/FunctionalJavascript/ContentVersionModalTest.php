<?php

namespace Drupal\Tests\ncms_ui\FunctionalJavascript;

use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\node\NodeInterface;

/**
 * Tests the revision comparison modal.
 *
 * @group ncms_ui
 */
class ContentVersionModalTest extends ContentTestBaseJavascript {

  /**
   * Tests that comparing revisions opens the visual diff in a modal.
   */
  public function testRevisionDiffModal() {
    $content_space = $this->createContentSpace();
    $node = $this->createArticleInContentSpace('Article with revisions', $content_space->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentInterface::class, $node);

    $node->set('body', 'Updated body');
    $node->setNewRevision(TRUE);
    $node->save();

    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space,
    ]));
    $versions_url = '/node/' . $node->id() . '/revisions';
    $this->drupalGet($versions_url);

    $assert_session = $this->assertSession();
    $assert_session->buttonExists('Compare selected versions');
    // WebDriver can fail to click the submit button below the responsive table.
    $this->getSession()->executeScript('document.getElementById("edit-submit").click();');
    $this->waitForAjaxToFinish();

    $modal = $assert_session->waitForElementVisible('css', '#drupal-modal');
    $this->assertNotEmpty($modal);
    $assert_session->elementTextContains('css', '.ui-dialog-title', 'Changes to Article with revisions');
    $iframe = $assert_session->elementExists('css', '#drupal-modal iframe#node-preview');
    $this->assertStringContainsString('/node/' . $node->id() . '/revisions/view/', $iframe->getAttribute('src'));
    $assert_session->addressEquals($versions_url);
  }

}
