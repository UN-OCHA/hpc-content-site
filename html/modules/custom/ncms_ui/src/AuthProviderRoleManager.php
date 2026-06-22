<?php

namespace Drupal\ncms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Manages auth provider marker roles for users.
 */
class AuthProviderRoleManager {

  /**
   * Role definitions keyed by provider id.
   */
  protected const ROLE_DEFINITIONS = [
    'social_auth_google' => [
      'id' => 'google_login',
      'label' => 'Authenticated via Google',
      'uuid' => '4f042491-969c-4854-8490-7b5e008b0a4c',
      'weight' => -7,
    ],
    'entraid' => [
      'id' => 'un_agency_login',
      'label' => 'Authenticated via UN Agency',
      'uuid' => '7466640a-1ad5-4d2f-a864-98ca51408864',
      'weight' => -6,
    ],
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns the configured provider role definitions.
   */
  public function getRoleDefinitions(): array {
    return self::ROLE_DEFINITIONS;
  }

  /**
   * Ensures the auth provider roles exist.
   */
  public function ensureRolesExist(): void {
    $role_storage = $this->entityTypeManager->getStorage('user_role');
    foreach ($this->getRoleDefinitions() as $role_definition) {
      $role = $role_storage->load($role_definition['id']);
      if ($role) {
        continue;
      }

      $role = $role_storage->create([
        'id' => $role_definition['id'],
        'label' => $role_definition['label'],
        'weight' => $role_definition['weight'],
        'is_admin' => FALSE,
        'uuid' => $role_definition['uuid'],
      ]);
      $role->save();
    }
  }

  /**
   * Assigns the role for a given provider to an account.
   *
   * Roles are additive on purpose so accounts can retain both markers.
   *
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\user\UserInterface $account
   *   The account to update.
   * @param string $provider_id
   *   The provider identifier.
   *
   * @return bool
   *   TRUE if the account was changed.
   */
  public function assignRoleForProvider($account, string $provider_id): bool {
    $roles = $this->getRoleDefinitions();
    if (empty($roles[$provider_id])) {
      return FALSE;
    }

    $account = $this->loadUser($account);
    if (!$account instanceof UserInterface) {
      return FALSE;
    }

    $role_id = $roles[$provider_id]['id'];
    if ($account->hasRole($role_id)) {
      return FALSE;
    }

    $account->addRole($role_id);
    $account->save();
    return TRUE;
  }

  /**
   * Loads a full user entity from an account object when needed.
   *
   * @param \Drupal\Core\Session\AccountInterface|\Drupal\user\UserInterface $account
   *   The account object.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user entity if available.
   */
  protected function loadUser($account): ?UserInterface {
    if ($account instanceof UserInterface) {
      return $account;
    }

    if (!$account instanceof AccountInterface || !$account->id()) {
      return NULL;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    return $user instanceof UserInterface ? $user : NULL;
  }

}
