<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\Core\Session\AccountInterface;

/**
 * Trait for entities supporting content spaces via ContentSpaceAwareInterface.
 *
 * This assumes that the implementing entity bundle has an entity reference
 * field named 'field_content_space'.
 */
trait ContentSpaceEntityTrait {

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
   * Check if the given account has content space based access to the content.
   *
   * @param Drupal\Core\Session\AccountInterface|null $account
   *   The account to check.
   *
   * @return bool
   *   TRUE if the account has access, FALSE otherwise.
   */
  public function hasContentSpaceAccess(AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    $content_space = $this->getContentSpace()?->id();
    if (!$content_space) {
      return FALSE;
    }
    $content_space_manager = $this->getContentSpaceManager();
    $user = $this->getEntityTypeManager()->getStorage('user')->load($account->id());
    return in_array($content_space, $content_space_manager->getValidContentSpaceIdsForUser($user));
  }

  /**
   * Get the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager
   */
  public function getEntityTypeManager() {
    return \Drupal::entityTypeManager();
  }

  /**
   * Get the content space manager.
   *
   * @return \Drupal\ncms_ui\ContentSpaceManager
   *   The content space manager.
   */
  public function getContentSpaceManager() {
    return \Drupal::service('ncms_ui.content_space.manager');
  }

}
