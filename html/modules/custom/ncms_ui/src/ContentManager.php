<?php

namespace Drupal\ncms_ui;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewExecutable;

/**
 * Manager class for content.
 *
 * This allows to load publishers from the current request.
 */
class ContentManager {

  use StringTranslationTrait;

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
   * ContentManager constructor.
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
      '#default_value' => $this->getCurrentContentSpace(),
      '#ajax' => [
        'callback' => [$this, 'setContentSpace'],
      ],
    ];
    return $build;
  }

  /**
   * Get the currently selected content space.
   *
   * @return int
   *   The id of the content space.
   */
  public function getCurrentContentSpace() {
    $content_space_id = $this->tempStore->get('content_space');
    if ($content_space_id === NULL) {
      $content_space_ids = $this->getValidContentSpaceIdsForCurrentUser();
      $content_space_id = !empty($content_space_ids) ? reset($content_space_ids) : NULL;
    }
    return $content_space_id;
  }

  /**
   * Set the currently selected content space.
   *
   * @param int $content_space_id
   *   The id of the content space.
   */
  public function setCurrentContentSpace($content_space_id) {
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
    if (!empty($query->tables['node_field_data'])) {
      $definition = [
        'table' => 'node__field_content_space',
        'field' => 'entity_id',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
      ];
      $join = $this->viewsJoin->createInstance('standard', $definition);
      $query->addRelationship('node__field_content_space', $join, 'content_space');
      $query->addWhere(0, 'node__field_content_space.field_content_space_target_id', $this->getCurrentContentSpace());
    }
  }

}
