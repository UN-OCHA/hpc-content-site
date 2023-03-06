<?php

namespace Drupal\ncms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Manager class for content.
 *
 * This allows to load publishers from the current request.
 */
class ContentManager {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The account object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $currentUser;

  /**
   * PublisherManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $current_user) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $this->entityTypeManager->getStorage('user')->load($current_user->id());
  }

  /**
   * Check if the content spaces for the current user should be restricted.
   *
   * @return bool
   *   TRUE if the user should be restricted to the content spaces set up for
   *   the account, FALSE if all content spaces can be used.
   */
  public function shouldRestrictContentSpaces() {
    return !$this->currentUser->hasPermission('administer nodes');
  }

  /**
   * Get the valid content spaces for the current user.
   *
   * @return int[]
   *   The content space ids
   */
  public function getValidContentSpaceIdsForCurrentUser() {
    return $this->getValidContentSpaceIdsForUser($this->currentUser);
  }

  /**
   * Get the valid content spaces for the given user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user object for which to retrieve the valid content spaces.
   *
   * @return int[]
   *   The content space ids
   */
  public function getValidContentSpaceIdsForUser(UserInterface $user) {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $content_spaces_field */
    $content_spaces_field = $user->get('field_content_spaces');
    $content_space_ids = array_map(function ($item) {
      return $item['target_id'];
    }, $content_spaces_field->getValue() ?? []);
    $content_space_ids = array_combine($content_space_ids, $content_space_ids);
    return $content_space_ids;
  }

}
