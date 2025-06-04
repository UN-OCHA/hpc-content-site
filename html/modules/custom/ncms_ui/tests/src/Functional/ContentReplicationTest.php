<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\ncms_ui\Entity\Content\Document;
use Drupal\node\NodeInterface;

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

    // Create a user with permission to manage content from both content
    // spaces.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
      $content_space_2,
    ]));
    $this->drupalGet($replicate_url);
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_1->id() . '"]');
    $this->assertSession()->elementExists('css', 'select[data-drupal-selector="edit-field-content-space"] option[value="' . $content_space_2->id() . '"]');
  }

  /**
   * Tests replication into the same or different content space.
   */
  public function testReplicateIntoContentSpaces() {
    $content_space_1 = $this->createContentSpace();
    $content_space_2 = $this->createContentSpace();

    // Create a user with permission to manage content from both content
    // spaces.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
      $content_space_2,
    ]));

    // Create an article.
    $article = $this->createArticleInContentSpace('Article 1 for Content space 1', $content_space_1->id());

    // Replicate into the same content space.
    $this->submitReplicationForm($article, [
      'field_content_space' => $content_space_1->id(),
    ]);
    $this->assertEquals($content_space_1->id(), $this->getContentSpace()->id());

    // Replicate into the other content space.
    $this->submitReplicationForm($article, [
      'field_content_space' => $content_space_2->id(),
    ]);
    $this->assertEquals($content_space_2->id(), $this->getContentSpace()->id());
  }

  /**
   * Test replicating a document together with it's content.
   */
  public function testReplicateDocumentWithContent() {
    $content_space_1 = $this->createContentSpace();
    $content_space_2 = $this->createContentSpace();

    // Create a user with permission to manage documents.
    $this->drupalLogin($this->createEditorUserWithContentSpaces([
      $content_space_1,
      $content_space_2,
    ], [
      'create document content',
      'edit own document content',
      'view document revisions',
      'revert document revisions',
    ]));

    // Create a document with a chapter and 2 articles.
    $document = $this->createDocumentInContentSpace('Document 1 for Content space 1', $content_space_1->id());

    // Create an article than can be added to a chapter.
    $article_1 = $this->createArticleInContentSpace('Article 1', $content_space_1->id());
    $article_2 = $this->createArticleInContentSpace('Article 2', $content_space_1->id());

    // Create a chapter and add it to the document.
    $this->createChapter([$article_1, $article_2], $document);

    // Replicate with "Replicate content" checked and a custom suffix.
    $this->submitReplicationForm($document, [
      'replicate_content[toggle]' => 'Checked',
      'replicate_content[suffix]' => '- replicated',
    ]);
    $this->assertSession()->statusMessageContains('2 articles have been replicated for ' . $document->label() . ' (Copy)', 'status');
    $this->assertSession()->pageTextContains($article_1->label() . ' - replicated');
    $this->assertSession()->pageTextContains($article_2->label() . ' - replicated');

    // Replicate with "Replicate content" checked and the default suffix.
    $this->submitReplicationForm($document, [
      'replicate_content[toggle]' => 'Checked',
    ]);
    $this->assertSession()->statusMessageContains('2 articles have been replicated for ' . $document->label() . ' (Copy)', 'status');
    $this->assertSession()->pageTextContains($article_1->label() . ' (Copy)');
    $this->assertSession()->pageTextContains($article_2->label() . ' (Copy)');

    // Replicate with "Replicate content" checked and no suffix.
    $this->submitReplicationForm($document, [
      'replicate_content[toggle]' => 'Checked',
      'replicate_content[suffix]' => '',
    ]);
    $this->assertSession()->statusMessageContains('2 articles have been replicated for ' . $document->label() . ' (Copy)', 'status');
    $this->assertSession()->pageTextNotContains($article_1->label() . ' (Copy)');
    $this->assertSession()->pageTextNotContains($article_2->label() . ' (Copy)');
    $this->assertSession()->pageTextContains($article_1->label());
    $this->assertSession()->pageTextContains($article_2->label());

    // Replicate without "Replicate content" checked and a different content
    // space.
    $this->submitReplicationForm($document, [
      'field_content_space' => $content_space_2->id(),
    ]);
    $this->assertSession()->statusMessageNotContains('2 articles have been replicated for ' . $document->label() . ' (Copy)', 'status');
    $this->assertSession()->pageTextNotContains($article_1->label() . ' (Copy)');
    $this->assertSession()->pageTextNotContains($article_2->label() . ' (Copy)');

  }

  /**
   * Helper function to submit the replicate form.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that will be replicated.
   * @param array $form_input
   *   The form values to submit.
   */
  private function submitReplicationForm(NodeInterface $node, $form_input): void {
    // Replicate into the same content space but check the "Replicate content"
    // options and use no suffix.
    $replicate_url = '/node/' . $node->id() . '/replicate';
    $this->drupalGet($replicate_url);
    if ($node instanceof Document) {
      $this->assertSession()->checkboxNotChecked('replicate_content[toggle]');
    }

    // Start the replication.
    $this->submitForm($form_input, 'Replicate');

    // And confirm the results.
    $this->assertSession()->statusMessageContains(ucfirst($node->type->entity->label()) . ' ' . $node->label() . ' has been replicated.');
    if (!empty($form_input['new_label_en'])) {
      $this->assertSession()->fieldValueEquals('Title', $form_input['new_label_en']);
    }
    else {
      $this->assertSession()->fieldValueEquals('Title', $node->label() . ' (Copy)');
    }
  }

}
