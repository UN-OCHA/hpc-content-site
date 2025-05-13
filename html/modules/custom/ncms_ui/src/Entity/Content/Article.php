<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;

/**
 * Bundle class for article nodes.
 */
class Article extends ContentBase {

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content');
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
        if ($document->isDeleted() || !$document->isPublished()) {
          continue;
        }
        $documents[$document->id()] = $document;
      }
    }
    return $documents;
  }

}
