<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\ncms_paragraphs\Entity\Paragraph\DocumentChapter;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Bundle class for document nodes.
 */
class Document extends ContentBase {

  const ARTICLES_FIELD = 'field_articles';

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl(): Url {
    return Url::fromUri('base:/admin/content/documents');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    // Update the article references.
    $this->updateArticleReferences();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    parent::postSave($storage, $update);

    // Update the document references of all contained articles.
    foreach ($this->getChapterParagraphs() as $chapter) {
      foreach ($chapter->getArticles() as $article) {
        $article->updateDocumentReferences();
        $article->setNewRevision(FALSE);
        $article->setSyncing(TRUE);
        $article->save();
      }
    }
  }

  /**
   * Get the chapter paragraphs.
   *
   * @return \Drupal\ncms_paragraphs\Entity\Paragraph\DocumentChapter[]
   *   Chapter paragraph objects.
   */
  public function getChapterParagraphs(): array {
    if (!$this->hasField('field_paragraphs') || $this->get('field_paragraphs')->isEmpty()) {
      return [];
    }
    return array_filter($this->get('field_paragraphs')->referencedEntities(), function (ParagraphInterface $paragraph) {
      return $paragraph instanceof DocumentChapter;
    });
  }

  /**
   * Update the article references for this document.
   */
  public function updateArticleReferences(): void {
    $articles_ids = [];

    if (!$this->isDeleted()) {
      $articles_ids = [];
      foreach ($this->getChapterParagraphs() as $chapter) {
        $articles_ids = array_merge($articles_ids, array_map(function (Article $article) {
          return $article->id();
        }, $chapter->getArticles()));
      }
    }

    $this->get(self::ARTICLES_FIELD)->setValue(array_map(function ($articles_id) {
      return ['target_id' => $articles_id];
    }, $articles_ids));
  }

  /**
   * Get the number of articles for this document.
   *
   * @return int
   *   The number of articles for this document
   */
  public function getArticleCount(): int {
    return $this->get(self::ARTICLES_FIELD)->count();
  }

}
