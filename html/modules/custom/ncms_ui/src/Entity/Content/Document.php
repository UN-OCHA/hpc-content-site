<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;

/**
 * Bundle class for document nodes.
 */
class Document extends ContentBase {

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content/documents');
  }

}
