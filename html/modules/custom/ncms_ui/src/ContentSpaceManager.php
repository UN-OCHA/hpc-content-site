<?php

namespace Drupal\ncms_ui;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ncms_ui\Entity\Taxonomy\ContentSpace;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewExecutable;

/**
 * Manager class for content spaces.
 */
class ContentSpaceManager {

  use StringTranslationTrait;

  /**
   * The realm used for node grants.
   */
  const NODE_ACCESS_REALM = 'ncms_ui_node_access_by_content_space';

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
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The views join manager service.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $viewsJoin;

  /**
   * ContentSpaceManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore service.
   * @param \Drupal\views\Plugin\ViewsHandlerManager $views_join
   *   The views join service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountInterface $current_user, PrivateTempStoreFactory $temp_store_factory, ViewsHandlerManager $views_join) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $this->entityTypeManager->getStorage('user')->load($current_user->id());
    $this->tempStore = $temp_store_factory->get('ncms_content_manager');
    $this->viewsJoin = $views_join;
  }

  /**
   * Check content space restrictions should be applied for the given path.
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if content space restrictions should be applied, FALSE otherwise.
   */
  public function isContentSpaceRestrictPath(string $path) {
    $paths = [
      '/admin/content',
      '/admin/content/articles',
      '/admin/content/documents',
      '/admin/content/stories',
      '/admin/content/media',
      '/admin/media/grid',
      '/admin/content/trash',
    ];
    foreach ($paths as $_path) {
      if ($path == $_path) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check if the content spaces for the current user should be restricted.
   *
   * @return bool
   *   TRUE if the user should be restricted to the content spaces set up for
   *   the account, FALSE if all content spaces can be used.
   */
  public function shouldRestrictContentSpaces($type = 'node') {
    if ($type == 'node') {
      return !$this->currentUser->hasPermission('administer nodes');
    }
    if ($type == 'media') {
      return !$this->currentUser->hasPermission('administer media');
    }
  }

  /**
   * Check if the current content space matches the account content spaces.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check.
   *
   * @return bool
   *   TRUE if the current content space is valid for the given account, FALSE
   *   otherwise.
   */
  public function userIsInValidContentSpace(AccountInterface $account = NULL) {
    $user = $account !== NULL ? $this->entityTypeManager->getStorage('user')->load($account->id()) : $this->currentUser;
    return in_array($this->getCurrentContentSpaceId(), $this->getValidContentSpaceIdsForUser($user));
  }

  /**
   * Get the valid content spaces for the current user.
   *
   * @return int[]
   *   The content space ids
   */
  public function getValidContentSpaceIdsForCurrentUser() {
    return $this->currentUser ? $this->getValidContentSpaceIdsForUser($this->currentUser) : [];
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
    }, array_filter($content_spaces_field->getValue() ?? []));
    $content_space_ids = array_combine($content_space_ids, $content_space_ids);
    return $content_space_ids;
  }

  /**
   * Get all content spaces.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of taxonomy terms.
   */
  public function getContentSpaces() {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'content_space',
    ]);
  }

  /**
   * Build a content space selector.
   *
   * @return array
   *   A render array.
   */
  public function buildContentSpaceSelector() {
    $content_spaces = $this->getContentSpaces();
    $content_space_ids_user = $this->getValidContentSpaceIdsForCurrentUser();

    $options_user = [];
    $options_global = [];
    foreach ($content_spaces as $term) {
      if (in_array($term->id(), $content_space_ids_user)) {
        $options_user[$term->id()] = $term->label();
      }
      else {
        $options_global[$term->id()] = $term->label();
      }
    }

    $build = [
      '#type' => 'select',
      '#title' => $this->t('Content space'),
      '#options' => [
        (string) $this->t('My content spaces') => $options_user,
        (string) $this->t('Other content spaces') => $options_global,
      ],
      '#default_value' => $this->getCurrentContentSpaceId(),
      '#ajax' => [
        'callback' => [$this, 'setContentSpace'],
      ],
    ];
    return $build;
  }

  /**
   * Get the currently active content space.
   *
   * @return \Drupal\ncms_ui\Entity\Taxonomy\ContentSpace
   *   The content space term object.
   */
  public function getCurrentContentSpace() {
    $content_space_id = $this->getCurrentContentSpaceId();
    $content_space = $this->entityTypeManager->getStorage('taxonomy_term')->load($content_space_id);
    return $content_space instanceof ContentSpace ? $content_space : NULL;
  }

  /**
   * Get the currently selected content space.
   *
   * @return int|null
   *   The id of the content space.
   */
  public function getCurrentContentSpaceId(): ?int {
    $content_space_id = $this->tempStore->get('content_space') ?? NULL;
    $content_spaces = $this->getContentSpaces();
    if ($content_space_id === NULL || !array_key_exists($content_space_id, $content_spaces)) {
      $user_content_space_ids = $this->getValidContentSpaceIdsForCurrentUser();
      $content_space_id = !empty($user_content_space_ids) ? reset($user_content_space_ids) : NULL;
    }
    return $content_space_id;
  }

  /**
   * Set the currently selected content space.
   *
   * @param int $content_space_id
   *   The id of the content space.
   */
  public function setCurrentContentSpaceId($content_space_id) {
    $this->tempStore->set('content_space', $content_space_id);
  }

  /**
   * Alter a views query.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\query\QueryPluginBase $query
   *   The query object.
   */
  public function alterViewsQuery(ViewExecutable $view, QueryPluginBase $query) {
    if ($view->current_display == 'page_find_content') {
      return;
    }
    $content_space = $this->getCurrentContentSpace();
    if (!$content_space) {
      return;
    }
    $content_space_id = $content_space->id();
    if (!empty($query->tables['node_field_data']) && $content_space_id) {
      $definition = [
        'table' => 'node__field_content_space',
        'field' => 'entity_id',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
      ];
      $join = $this->viewsJoin->createInstance('standard', $definition);
      $query->addRelationship('node__field_content_space', $join, 'content_space');
      $query->addWhere(0, 'node__field_content_space.field_content_space_target_id', $content_space_id);
    }
  }

  /**
   * Alter a media access query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   The alterable query object.
   */
  public function alterMediaAccessQuery(AlterableInterface $query) {
    if (!$this->shouldRestrictContentSpaces('media')) {
      return;
    }
    // We're only interested in applying our media access restrictions to
    // SELECT queries.
    if (!($query instanceof SelectInterface)) {
      return;
    }

    // The tables in the query. This can include media entity tables and other
    // tables. Tables might be joined more than once, with aliases.
    $query_tables = $query->getTables();
    $base_table = NULL;
    $valid_tables = ['base_table', 'media_field_data'];
    foreach ($valid_tables as $valid_table) {
      if (!empty($query_tables[$valid_table])) {
        $base_table = $query_tables[$valid_table];
      }
    }
    if (!$base_table) {
      return;
    }

    // The tables belonging to media entity storage.
    /** @var \Drupal\media\MediaStorage  $media_storage */
    $media_storage = $this->entityTypeManager->getStorage('media');
    $table_mapping = $media_storage->getTableMapping();
    $media_tables = $table_mapping->getTableNames();
    $content_space_table = 'media__field_content_space';
    if (!in_array($content_space_table, $media_tables)) {
      return;
    }
    // Join in the content space field table and the access condition.
    $query->join($content_space_table, 'content_space', $base_table['alias'] . '.mid = content_space.entity_id');
    $query->condition('content_space.field_content_space_target_id', $this->getCurrentContentSpaceId());
  }

}
