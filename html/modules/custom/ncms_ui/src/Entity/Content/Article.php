<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;
use Drupal\ncms_paragraphs\Entity\Paragraph\SubArticle;

/**
 * Bundle class for article nodes.
 */
class Article extends ContentBase {

  const FIELD_DOCUMENTS = 'field_documents';

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl(): Url {
    return Url::fromUri('base:/admin/content');
  }

  /**
   * Update the document references for this article.
   */
  public function updateDocumentReferences(): void {
    $document_ids = [];

    if (!$this->isDeleted()) {
      $document_ids = array_map(function ($document) {
        return $document->id();
      }, $this->getDocuments());
    }

    $this->get(self::FIELD_DOCUMENTS)->setValue(array_map(function ($document_id) {
      return ['target_id' => $document_id];
    }, $document_ids));
  }

  /**
   * Get the documents that the article belongs to.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Document[]
   *   The documents that the article belongs to.
   */
  public function getDocuments(): array {
    /** @var \Drupal\paragraphs\Entity\Paragraph[] $article_paragraphs */
    $article_paragraphs = $this->entityTypeManager()->getStorage('paragraph')->loadByProperties([
      'type' => ['article', 'document_chapter'],
      'field_articles' => [$this->id()],
    ]);
    $documents = [];
    foreach ($article_paragraphs as $paragraph) {
      $document = $paragraph->getParentEntity();
      if ($document instanceof Document) {
        if ($document->isDeleted()) {
          continue;
        }
        $documents[$document->id()] = $document;
      }
    }
    return $documents;
  }

  /**
   * Get all sub article paragraphs for this article.
   *
   * @return \Drupal\ncms_paragraphs\Entity\Paragraph\SubArticle[]
   *   An array of sub article paragraphs.
   */
  public function getSubArticleParagraphs(): array {
    /** @var \Drupal\paragraphs\Entity\Paragraph[] $paragraphs */
    $paragraphs = $this->entityTypeManager()->getStorage('paragraph')->loadByProperties([
      'type' => ['sub_article'],
      'parent_type' => 'node',
      'parent_id' => $this->id(),
    ]);
    $paragraphs = array_filter($paragraphs, fn ($paragraph) => $paragraph instanceof SubArticle);
    return $paragraphs;
  }

  /**
   * Check if the article has sub articles.
   *
   * Because the article field in a sub article paragraph is mandatory, we can
   * assume that there are indeed sub articles if there is a non empty set of
   * sub article paragraphs.
   *
   * @return bool
   *   TRUE if the article has sub articles, FALSE otherwise.
   */
  public function hasSubArticles(): bool {
    return !empty($this->getSubArticleParagraphs());
  }

}
