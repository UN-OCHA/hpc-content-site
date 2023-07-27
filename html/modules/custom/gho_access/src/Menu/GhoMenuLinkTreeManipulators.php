<?php

namespace Drupal\gho_access\Menu;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a couple of menu link tree manipulators.
 *
 * Overrides \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators.
 *
 * Adds special check for node access.
 *
 * This class provides menu link tree manipulators to:
 * - perform render cached menu-optimized access checking
 * - optimized node access checking
 * - generate a unique index for the elements in a tree and sorting by it
 * - flatten a tree (i.e. a 1-dimensional tree)
 */
class GhoMenuLinkTreeManipulators extends DefaultMenuLinkTreeManipulators {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators object.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface|null $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(AccessManagerInterface $access_manager, AccountInterface $account, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler = NULL, LanguageManagerInterface $language_manager) {
    $this->accessManager = $access_manager;
    $this->account = $account;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function checkNodeAccess(array $tree) {
    // Nothing more todo that the default access check if the user
    // is allowed to access content in any language.
    if ($this->account->hasPermission('view untranslated content')) {
      return parent::checkNodeAccess($tree);
    }

    $node_links = [];
    // Calling this function denies access to all the links. Access is granted
    // by the code below for the node links that match the criteria.
    // @see \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators::checkNodeAccess()
    $this->collectNodeLinks($tree, $node_links);
    if ($node_links) {
      // Exit early if the visibility for the language is not enabled.
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if (!gho_access_check_language_visibility($langcode)) {
        return $tree;
      }

      // The code below is the same as the parent class except for the condition
      // on the language code below.
      $nids = array_keys($node_links);

      $query = $this->entityTypeManager->getStorage('node')->getQuery();
      $query->condition('nid', $nids, 'IN');

      // Allows admins to view all nodes, by both disabling node_access
      // query rewrite as well as not checking for the node status. The
      // 'view own unpublished nodes' permission is ignored to not require cache
      // entries per user.
      $access_result = AccessResult::allowed()->cachePerPermissions();
      if ($this->account->hasPermission('bypass node access')) {
        $query->accessCheck(FALSE);
      }
      else {
        $access_result->addCacheContexts(['user.node_grants:view']);
        $query->condition('status', NodeInterface::PUBLISHED);
        // This is to prevent language mismatch.
        $query->condition('langcode', $langcode, 'IN');
        $query->accessCheck(TRUE);
      }

      $nids = $query->execute();
      foreach ($nids as $nid) {
        foreach ($node_links[$nid] as $key => $link) {
          $node_links[$nid][$key]->access = $access_result;
        }
      }
    }

    return $tree;
  }

}
