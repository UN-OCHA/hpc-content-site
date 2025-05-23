<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;
use Drupal\ncms_ui\Entity\Content\Article;

/**
 * Entity class for paragraphs of type document chapter.
 */
class DocumentChapter extends NcmsParagraphBase {

  /**
   * Get the articles in the chapter.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Article[]
   *   The articles nodes referenced by the chapter.
   */
  public function getArticles() {
    $articles = $this->get('field_articles')->referencedEntities();
    return array_filter($articles, function ($article) {
      return $article instanceof Article;
    });
  }

}
