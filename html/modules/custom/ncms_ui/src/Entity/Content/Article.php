<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;

/**
 * Bundle class for article nodes.
 */
class Article extends ContentBase {

  const FIELD_DOCUMENTS = 'field_documents';

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content');
  }

  /**
   * Update the document references for this article.
   */
  public function updateDocumentReferences() {
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
  public function getDocuments() {
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

}
