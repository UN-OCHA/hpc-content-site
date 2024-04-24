<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Trait for entities supporting iframe display and preview.
 */
trait IframeDisplayContentTrait {

  /**
   * {@inheritdoc}
   */
  public function getIframePreviewUrl(array $options = []) {
    if (!$this instanceof NodeInterface) {
      throw new \Exception('The IframeDisplayContentTrait can only be used by node entities');
    }
    return new Url('entity.node.preview', [
      'node_preview' => $this->uuid(),
      'view_mode_id' => 'full',
    ], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getIframeStandaloneUrl(array $options = []) {
    if (!$this instanceof NodeInterface) {
      throw new \Exception('The IframeDisplayContentTrait can only be used by node entities');
    }
    return new Url('entity.node.standalone', [
      'node' => $this->id(),
    ], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getIframeStandaloneRevisionUrl(array $options = []) {
    if (!$this instanceof NodeInterface) {
      throw new \Exception('The IframeDisplayContentTrait can only be used by node entities');
    }
    return new Url('entity.node_revision.standalone', [
      'node' => $this->id(),
      'node_revision' => $this->getRevisionId(),
    ], $options);
  }

}
