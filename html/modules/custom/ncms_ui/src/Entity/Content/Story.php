<?php

namespace Drupal\ncms_ui\Entity\Content;

use Drupal\Core\Url;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;
use Drupal\ncms_ui\Entity\EntityOverviewInterface;
use Drupal\ncms_ui\Entity\IframeDisplayContentInterface;
use Drupal\ncms_ui\Traits\ContentSpaceEntityTrait;
use Drupal\ncms_ui\Traits\IframeDisplayContentTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for story nodes.
 */
class Story extends Node implements ContentSpaceAwareInterface, EntityOverviewInterface, IframeDisplayContentInterface {

  use ContentSpaceEntityTrait;
  use IframeDisplayContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getOverviewUrl() {
    return Url::fromUri('base:/admin/content/stories');
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleLabel() {
    return $this->type->entity->label();
  }

}
