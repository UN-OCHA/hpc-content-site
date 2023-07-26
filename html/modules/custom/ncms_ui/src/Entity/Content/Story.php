<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\ncms_ui\Entity\EntityOverviewInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for story nodes.
 */
class Story extends Node implements ContentSpaceAwareInterface, EntityOverviewInterface {

  use ContentSpaceEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content/stories');
  }

}
