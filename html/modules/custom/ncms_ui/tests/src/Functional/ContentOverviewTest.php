<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\node\NodeInterface;

/**
 * Tests logic around the content overview page.
 *
 * @group ncms_ui
 */
class ContentOverviewTest extends ContentTestBase {

  /**
   * Tests that the correct node versions show up in the content overview.
   */
  public function testContentOverviewPage() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentBase::class, $node_1_1);
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $node_1_1 */

    // Define some urls.
    $overview_url = '/admin/content';
    $edit_url = '/node/' . $node_1_1->id() . '/edit';

    // Define some xpath selectors.
    $thead_xpath = '//div[@class="view-content"]//table/thead';
    $tbody_xpath = '//div[@class="view-content"]//table/tbody';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    $assert_session = $this->assertSession();

    $this->drupalGet($overview_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr', 1);

    // Assert table header.
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[1]', 'Title');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[2]', 'Latest version');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[3]', 'Latest published');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[4]', 'Status');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[5]', 'Changed');
    $assert_session->elementContains('xpath', $thead_xpath . '/tr[1]/th[6]', '');

    // Assert table body.
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', 'Article 1 for Content space 1');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[1]/a[@href="/node/1/iframe" and @class="use-ajax"]');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[2]', '#1 (Draft)');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[2]/a[@href="/node/1/iframe/1" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[3]', '');
    $assert_session->elementNotExists('xpath', $tbody_xpath . '/tr[1]/td[3]/a[@href="/node/1/iframe/1" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[4]', 'Draft');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/edit?destination=/admin/content"]', 'Edit');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/replicate?destination"]', 'Replicate');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/revisions"]', 'Versions');

    // Create a second revision and publish it.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content');
    $this->getSession()->getPage()->pressButton('Save and publish');

    // Go back to the versions page.
    $this->drupalGet($overview_url);

    // Assert table body.
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', 'Article 1 for Content space 1');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[1]/a[@href="/node/1/iframe" and @class="use-ajax"]');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[2]', '#2 (Published)');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[2]/a[@href="/node/1/iframe/2" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[3]', '#2 (Published)');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[3]/a[@href="/node/1/iframe/2" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[4]', 'Published');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/edit?destination=/admin/content"]', 'Edit');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/replicate?destination"]', 'Replicate');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/revisions"]', 'Versions');

    // Create a third revision.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-title-0-value', 'New draft title');
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content draft');
    $this->getSession()->getPage()->pressButton('Save as draft');

    // Go back to the versions page.
    $this->drupalGet($overview_url);

    // Assert table body.
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', 'Article 1 for Content space 1');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[1]/a[@href="/node/1/iframe" and @class="use-ajax"]');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[2]', '#3 (Draft)');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[2]/a[@href="/node/1/iframe/3" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[3]', '#2 (Published)');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[1]/td[3]/a[@href="/node/1/iframe/2" and @class="use-ajax"]');
    $assert_session->elementTextEquals('xpath', $tbody_xpath . '/tr[1]/td[4]', 'Published with newer draft');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/edit?destination=/admin/content"]', 'Edit');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/replicate?destination"]', 'Replicate');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/revisions"]', 'Versions');
  }

  /**
   * Test the trash bin feature.
   */
  public function testTrashBin() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id(), NodeInterface::NOT_PUBLISHED);
    $this->assertInstanceOf(ContentBase::class, $node_1_1);
    $this->assertContentModerationTableEntry($node_1_1);
    /** @var \Drupal\ncms_ui\Entity\Content\ContentBase $node_1_1 */

    // Define some urls.
    $overview_url = '/admin/content';
    $trash_url = '/admin/content/trash';
    $edit_url = '/node/' . $node_1_1->id() . '/edit';
    $versions_url = '/node/' . $node_1_1->id() . '/revisions';

    // Define some xpath selectors.
    $tbody_xpath = '//div[@class="view-content"]//table/tbody';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    $assert_session = $this->assertSession();

    // Publish the version.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->pressButton('Save and publish');

    // Create a second revision.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content draft');
    $this->getSession()->getPage()->pressButton('Save as draft');

    // Go to the overview page, find the trash button and click it.
    $this->drupalGet($overview_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr[1]/td', 6);
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', 'Article 1 for Content space 1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/trash"]', 'Move to trash');
    $this->getSession()->getPage()->find('xpath', $tbody_xpath . '/tr[1]/td[6]')->findLink('Move to trash')->click();
    $assert_session->pageTextContains('This will remove this article, including already published versions, from public display anywhere. Are you sure?');
    $assert_session->buttonExists('Ok');
    $this->getSession()->getPage()->pressButton('Ok');
    $assert_session->pageTextContains('Article Article 1 for Content space 1 has been moved to the trash bin.');

    // Go back to the overview page and confirm the list is empty now.
    $this->drupalGet($overview_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr', 0);

    // Confirm access denied on the node edit and versions pages.
    $this->drupalGet($edit_url);
    $assert_session->pageTextContains('Access denied');
    $this->drupalGet($versions_url);
    $assert_session->pageTextContains('Access denied');

    // Go to the trash page and confirm the deleted node is listed there now.
    $this->drupalGet($trash_url);
    $assert_session->elementsCount('xpath', $tbody_xpath . '/tr[1]/td', 5);
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[5]//a[@href="/node/' . $node_1_1->id() . '/restore"]', 'Restore');
    $this->assertContentModerationTableEntry($node_1_1);

    // Now click the restore button.
    $this->getSession()->getPage()->find('xpath', $tbody_xpath . '/tr[1]/td[5]')->findLink('Restore')->click();
    $assert_session->pageTextContains('This will restore this article and make it automatically publicly available again if there are any published versions. Are you sure?');
    $assert_session->buttonExists('Ok');
    $this->getSession()->getPage()->pressButton('Ok');
    $assert_session->pageTextContains('Article Article 1 for Content space 1 has been restored from the trash bin.');
    $this->assertContentModerationTableEntry($node_1_1);

    // Back to the overview to confirm that article is really back.
    $this->drupalGet($overview_url);
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[1]', 'Article 1 for Content space 1');
    $assert_session->elementContains('xpath', $tbody_xpath . '/tr[1]/td[6]//a[@href="/node/' . $node_1_1->id() . '/trash"]', 'Move to trash');

    // And have a look at the version page to confirm that the published
    // revision is the current one (the second).
    $this->drupalGet($versions_url);
    $tbody_xpath = '//table[@data-drupal-selector="edit-node-revisions-table"]/tbody';
    $row = $this->getSession()->getPage()->find('xpath', $tbody_xpath . '/tr[2]');
    $this->assertEquals(TRUE, $row->hasClass('revision-current'));
    $this->assertEquals(TRUE, $row->hasClass('published'));
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[2][contains(@class, "revision-current")]');
    $assert_session->elementExists('xpath', $tbody_xpath . '/tr[2][contains(@class, "revision-current") and contains(@class, "published")]');
  }

}
