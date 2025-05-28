<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;
use Drupal\ncms_ui\Entity\Content\Article;

/**
 * Entity class for paragraphs of type document chapter.
 */
class DocumentChapter extends NcmsParagraphBase {

  const TITLE_FIELD = 'field_title';
  const ARTICLES_FIELD = 'field_articles';

  /**
   * Get the title of the chapter.
   *
   * @return string
   *   The title of the chapter.
   */
  public function getTitle(): string {
    return $this->get(self::TITLE_FIELD)->value;
  }

  /**
   * Set the title of the chapter.
   *
   * @param string $title
   *   The title of the chapter.
   */
  public function setTitle(string $title): void {
    $this->get(self::TITLE_FIELD)->setValue($title);
  }

  /**
   * Get the articles in the chapter.
   *
   * @return \Drupal\ncms_ui\Entity\Content\Article[]
   *   The articles nodes referenced by the chapter.
   */
  public function getArticles(): array {
    $articles = $this->get(self::ARTICLES_FIELD)->referencedEntities();
    return array_filter($articles, function ($article) {
      return $article instanceof Article;
    });
  }

  /**
   * Replace an article in the chapter with a new one.
   *
   * @param \Drupal\ncms_ui\Entity\Content\Article $original_article
   *   The article that should be replace.
   * @param \Drupal\ncms_ui\Entity\Content\Article $new_article
   *   The article that it should be replaced with.
   */
  public function replaceArticle(Article $original_article, Article $new_article): void {
    $value = $this->get(self::ARTICLES_FIELD)->getValue();
    foreach ($value as $key => $item) {
      if ($item['target_id'] != $original_article->id()) {
        continue;
      }
      $value[$key] = ['target_id' => $new_article->id()];
    }
    $this->get(self::ARTICLES_FIELD)->setValue($value);
  }

  /**
   * Remove all articles from a chapter.
   */
  public function removeArticles(): void {
    $this->get(self::ARTICLES_FIELD)->setValue([]);
  }

}
