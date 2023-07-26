<?php

namespace Drupal\ncms_ui\Entity\Taxonomy;

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Bundle class for content space terms.
 */
class ContentSpace extends Term {

  /**
   * Get the tags for this content space.
   *
   * @return array
   *   An array of tag labels.
   */
  public function getTags() {
    $tag_terms = $this->get('field_major_tags')->referencedEntities();
    return array_map(function (TermInterface $term) {
      return $term->label();
    }, $tag_terms);
  }

}
