<?php

namespace Drupal\Tests\ncms_ui\Functional;

/**
 * Tests replication of content into different content spaces.
 *
 * @group ncms_ui
 */
class ContentReplicationTest extends ContentTestBase {

  /**
   * Tests that nodes show up only in their respective content space.
   */
  public function testReplicateContentSpaceOptions() {
    // Create content spaces.
    $content_space_1 = $this->createContentSpace();
    $content_space_2 = $this->createContentSpace();

    // Create node for content space 1.
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());
    $replicate_url = '/node/' . $node_1_1->id() . '/replicate';

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
    ]));
    $this->drupalGet($replicate_url);
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_1->id() . '"]');
    $this->assertSession()->elementNotExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_2->id() . '"]');

    // Create a user with permission to manage content from content spaces 1.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
      $content_space_2,
    ]));
    $this->drupalGet($replicate_url);
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_1->id() . '"]');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_2->id() . '"]');
  }

  /**
   * Tests replication inside the same content space.
   */
  public function testReplicateIntoContentSpaces() {
    $content_space_1 = $this->createContentSpace();
    $node_1_1 = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());

    $content_space_2 = $this->createContentSpace();

    // Create a user with permission to manage content from content spaces 1
    // and 2.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
      $content_space_2,
    ]));

    $replicate_url = '/node/' . $node_1_1->id() . '/replicate';

    // Replicate into the same content space.
    $this->drupalGet($replicate_url);
    $this->getSession()->getPage()->hasSelect('Content space');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_1->id() . '"]');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_2->id() . '"]');
    $this->getSession()->getPage()->selectFieldOption('Content space', $content_space_1->id());
    $this->getSession()->getPage()->pressButton('Replicate');

    $this->assertEquals($content_space_1->id(), $this->getContentSpace()->id());

    // Replicate into the other content space.
    $this->drupalGet($replicate_url);
    $this->getSession()->getPage()->hasSelect('Content space');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_1->id() . '"]');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_2->id() . '"]');
    $this->getSession()->getPage()->selectFieldOption('Content space', $content_space_2->id());
    $this->getSession()->getPage()->pressButton('Replicate');

    $this->assertEquals($content_space_2->id(), $this->getContentSpace()->id());
  }

}
