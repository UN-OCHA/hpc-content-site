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

}
