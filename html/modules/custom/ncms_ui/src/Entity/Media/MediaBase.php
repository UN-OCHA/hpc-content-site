<?php

namespace Drupal\ncms_ui\Entity\Media;

use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\ncms_ui\Entity\MediaSpaceAwareInterface;

/**
 * Bundle base class for media entities.
 */
abstract class MediaBase extends Media implements MediaSpaceAwareInterface {

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    if ($rel == 'canonical' && !$this->access('update')) {
      // The canonical url for media entities is the edit url. In cases where
      // access to the edit form is forbidden, we need to use a different url
      // here, so we use the actual image url.
      $thumbnail_uri = $this->getThumbnailUri(FALSE);
      $path = self::filUrlGenerator()->generateAbsoluteString($thumbnail_uri);
      return Url::fromUri($path);
    }
    return parent::toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getContentSpace() {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    return $this->get('field_content_space')->entity ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentSpace($content_space_id) {
    if (!$this->hasField('field_content_space')) {
      return NULL;
    }
    $this->get('field_content_space')->setValue(['target_id' => $content_space_id]);
  }

  /**
   * Get the file url generator service.
   *
   * @return \Drupal\Core\File\FileUrlGenerator
   *   The file url generator service.
   */
  private static function filUrlGenerator() {
    return \Drupal::service('file_url_generator');
  }

}
