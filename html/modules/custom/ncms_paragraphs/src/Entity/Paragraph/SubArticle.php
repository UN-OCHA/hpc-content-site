<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;
use Drupal\ncms_ui\Entity\Content\Article;

/**
 * Entity class for paragraphs of type sub article.
 */
class SubArticle extends NcmsParagraphBase {

  const ARTICLE_FIELD = 'field_article';

  /**
   * Get the article for this paragraph.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Article|null
   *   An article object if found.
   */
  public function getArticle(): ?Article {
    $articles = array_filter($this->referencedEntities(), fn ($entity) => $entity instanceof Article);
    return !empty($articles) ? reset($articles) : NULL;
  }

  /**
   * Set the article for this paragraph.
   *
   * @param \Drupal\ncms_ui\Entity\Content\Article $article
   *   The article that should set.
   */
  public function setArticle(Article $article): void {
    $this->get(self::ARTICLE_FIELD)->setValue(['target_id' => $article->id()]);
  }

}
