<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

use Drupal\ncms_paragraphs\Entity\NcmsParagraphBase;

/**
 * Entity class for paragraphs of type article_card_list.
 */
class ArticleCardList extends NcmsParagraphBase {

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    if ($this->get('field_emphasize_first_row')->value) {
      $variables['attributes']['class'][] = 'gho-article-card-list--show-2col';
    }
  }

}
