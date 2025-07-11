<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Trait for retrieving the bundle label of an entity.
 *
 * To be used in entity bundle classes.
 */
trait EntityBundleLabelTrait {

  /**
   * Get the bundel label for the entity.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|null
   *   The bundle label or NULL.
   */
  public function getBundleLabel() {
    if (!$this instanceof ContentEntityInterface) {
      return NULL;
    }
    $bundle_type_id = $this->getEntityType()->getBundleEntityType();
    $bundle_label = \Drupal::entityTypeManager()
      ->getStorage($bundle_type_id)
      ->load($this->bundle())
      ->label();
    return $bundle_label;
  }

}
