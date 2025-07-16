<?php

namespace Drupal\ncms_paragraphs\Entity\Paragraph;

/**
 * Entity class for paragraphs of type top figures (small).
 */
class TopFiguresSmall extends TopFigures {

  const USE_EMPHASIS = FALSE;

  /**
   * {@inheritdoc}
   */
  public function useEmphasis() {
    return self::USE_EMPHASIS;
  }

}
