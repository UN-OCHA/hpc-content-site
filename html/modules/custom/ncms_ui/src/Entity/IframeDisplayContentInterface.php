<?php

namespace Drupal\ncms_ui\Entity;

/**
 * Defines an interface for entities with content spaces.
 */
interface IframeDisplayContentInterface {

  /**
   * Return the URL for previews inside iframes.
   *
   * @return \Drupal\Core\Url
   *   The url of the preview page.
   */
  public function getIframePreviewUrl(array $options = []);

  /**
   * Return the URL for a standalone display inside iframes.
   *
   * @return \Drupal\Core\Url
   *   The url of the standalone page.
   */
  public function getIframeStandaloneUrl(array $options = []);

  /**
   * Return the URL for a revisions standalone display inside iframes.
   *
   * @return \Drupal\Core\Url
   *   The url of the standalone page for a revision.
   */
  public function getIframeStandaloneRevisionUrl(array $options = []);

  /**
   * Get the bundle label for this content.
   *
   * @return string
   *   The bundle label.
   */
  public function getBundleLabel();

}
