<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;

/**
 * Tests logic around content versions.
 *
 * @group ncms_ui
 */
class ContentVersionTest extends ContentTestBase {

  /**
   * Tests that nodes in the backend show version information.
   */
  public function testVersionOverviewPage() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentBase::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $node_1_1 */

    // Define some urls.
    $edit_url = '/node/' . $node_1_1->id() . '/edit';
    $versions_url = '/node/' . $node_1_1->id() . '/revisions';
    $preview_url = '/node/' . $node_1_1->id() . '/iframe/' . $node_1_1->getRevisionId();

    // Define some xpath selectors.
    $thead_xpath = '//table[contains(@class, "diff-revisions")]/thead';
    $tbody_xpath = '//table[contains(@class, "diff-revisions")]/tbody';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    $assert_session = $this->assertSession();

    $this->drupalGet($versions_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr', 1);

    // Assert table header.
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[1]', 'Version');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[2]', 'Title');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[3]', 'User');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[4]', 'Created');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[5]', 'Status');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[6]', 'Operations');

    // Assert table body.
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', '1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[2]', 'Article 1 for Content space 1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[3]', 'Anonymous');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[4]/a[@href="' . $preview_url . '" and @class="use-ajax"]');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[5]', 'Draft');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/revisions/1/publish"]', 'Publish');

    // Create a second revision.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content');
    $this->getSession()->getPage()->pressButton('Save as draft');

    // Go back to the versions page. Confirm there are 2 revisions listed now.
    // Both are unpublished but their status is labeled "Draft" for the most
    // current one and "Archived" for the earlier one.
    // Note that the index fo the <td> elements has increased now, because with
    // more than a single revision, the diff module adds 2 radio button columns
    // to the table.
    $this->drupalGet($versions_url);
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[8]', 'Operations');
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr', 2);
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', '2');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[5]', 'Draft');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/2/publish"]', 'Publish');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[1]', '1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[5]', 'Archived');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/1/publish"]', 'Publish');

    // Publish the current revision.
    $this->getSession()->getPage()->find('xpath', $tbody_xpath . '/tr[1]/td[8]')->findLink('Publish')->click();
    $assert_session->elementContains('xpath', '//div[@class="messages__content"]', 'Version #2 has been published');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[5]', 'Published');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/2/unpublish"]', 'Unpublish');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[5]', 'Archived');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/1/publish"]', 'Publish');

    // Create a third revision.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content draft');
    $this->getSession()->getPage()->pressButton('Save as draft');

    // Go back to the versions page. Confirm there are 3 revisions listed now.
    $this->drupalGet($versions_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr', 3);
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', '3');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[5]', 'Draft');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/3/publish"]', 'Publish');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[1]', '2');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[5]', 'Published');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[2]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/2/unpublish"]', 'Unpublish');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[3]/td[1]', '1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[3]/td[5]', 'Archived');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[3]/td[8]//a[@href="/node/' . $node_1_1->id() . '/revisions/1/publish"]', 'Publish');

  }

}
