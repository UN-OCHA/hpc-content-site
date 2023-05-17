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

    // Create a second revision.
    $this->drupalGet($edit_url);
    $this->getSession()->getPage()->fillField('edit-body-0-value', 'Test content');
    $this->getSession()->getPage()->pressButton('Publish');

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
    $this->getSession()->getPage()->pressButton('Create draft (leave current version published)');

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

}
