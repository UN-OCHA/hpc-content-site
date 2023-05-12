<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\diff\Form\RevisionOverviewForm;
use Drupal\ncms_ui\Entity\ContentVersionInterface;

/**
 * Form alter class for the revision history overview form.
 */
class RevisionOverviewFormAlter {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route matching service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * The page manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route matching service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\DateTime\DateFormatter $date
   *   The date service.
   * @param \Drupal\Core\Pager\PagerManagerInterface $page_manager
   *   The pager manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, RouteMatchInterface $route_match, RendererInterface $renderer, DateFormatter $date, PagerManagerInterface $page_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->date = $date;
    $this->pageManager = $page_manager;
  }

  /**
   * Alter the revision history overview form.
   */
  public function alterForm(&$form, FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if (!$form_object instanceof RevisionOverviewForm) {
      return;
    }

    // Looking for the submit buttons of the diff module.
    if ($form['submit_top']['#type'] ?? NULL == 'submit') {
      $form['submit_top']['#value'] = $this->t('Compare selected versions');
    }
    if ($form['submit_bottom']['#type'] ?? NULL == 'submit') {
      $form['submit_bottom']['#value'] = $this->t('Compare selected versions');
    }

    // Get the curent node.
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof ContentVersionInterface) {
      return;
    }

    $account = $this->currentUser;
    $type = $node->getType();
    $rev_delete_perm = $account->hasPermission("delete $type revisions") ||
      $account->hasPermission('delete all revisions') ||
      $account->hasPermission('administer nodes');
    $delete_permission = $rev_delete_perm && $node->access('delete');

    /** @var \Drupal\Node\NodeStorageInterface $node_storage */
    $node_storage = $this->entityTypeManager->getStorage('node');

    // Get all revision ids.
    $revision_ids = array_reverse($node_storage->revisionIds($node));

    // See if we need to use an offset.
    $pager = $this->pageManager->getPager();
    if ($pager && $pager->getCurrentPage()) {
      $offset = $pager->getLimit() * $pager->getCurrentPage();
      $revision_ids = array_slice($revision_ids, $offset, $pager->getLimit());
    }

    /** @var \Drupal\ncms_ui\Entity\ContentBase[] $revisions */
    $revisions = array_values($node_storage->loadMultipleRevisions($revision_ids));

    $header = &$form['node_revisions_table']['#header'];

    // Modify the columns headers.
    unset($header['revision']);
    $header = [
      'version' => $this->t('Version'),
      'title' => $this->t('Title'),
      'user' => $this->t('User'),
      'created' => $this->t('Created'),
      'status' => $this->t('Status'),
    ] + $header;

    // Now modify the rows.
    foreach (Element::children($form['node_revisions_table']) as $key) {
      $row = &$form['node_revisions_table'][$key];
      $revision = $revisions[$key];
      unset($row['revision']);

      $revision_date = $this->date->format($revision->getRevisionCreationTime(), 'short');
      $link = Link::fromTextAndUrl($revision_date, new Url('entity.node.revision', [
        'node' => $revision->id(),
        'node_revision' => $revision->getRevisionId(),
      ]));

      $row_classes = array_merge($row['#attributes']['class'] ?? [], [Html::getClass($revision->getVersionStatus())]);
      if ($revision->isDefaultRevision()) {
        $row_classes[] = 'revision-current';
      }
      elseif (in_array('revision-current', $row_classes)) {
        $row_classes = array_diff($row_classes, ['revision-current']);
      }
      $row['#attributes']['class'] = $row_classes;

      $row = [
        'version' => [
          '#type' => 'markup',
          '#markup' => $revision->getVersionId(),
        ],
        'title' => [
          '#type' => 'markup',
          '#markup' => $revision->label(),
        ],
        'user' => [
          '#type' => 'markup',
          '#markup' => $revision->getRevisionUser()->label(),
        ],
        'created' => $link->toRenderable(),
        'status' => [
          '#type' => 'markup',
          '#markup' => $revision->getVersionStatus(),
        ],
      ] + $row;

      $route_params = [
        'node' => $revision->id(),
        'node_revision' => $revision->getRevisionId(),
      ];

      $last_cell = &$row['operations'];
      unset($last_cell['#links']['delete']);
      if ($revision->isDefaultRevision()) {
        $last_cell = [
          '#type' => 'operations',
          '#links' => [],
        ];
        if ($revision->isPublished()) {
          $last_cell['#links']['unpublish'] = [
            'title' => $this->t('Unpublish'),
            'url' => Url::fromRoute('entity.node.revision.unpublish', $route_params),
          ];
        }
        else {
          $last_cell['#links']['publish'] = [
            'title' => $this->t('Publish'),
            'url' => Url::fromRoute('entity.node.revision.publish', $route_params),
          ];
        }
      }
      elseif (array_key_exists('revert', $last_cell['#links'] ?? [])) {
        // We have a revert link, so the user has permission to update this
        // revision.
        if ($revision->isPublished()) {
          $last_cell['#links']['unpublish'] = [
            'title' => $this->t('Unpublish'),
            'url' => Url::fromRoute('entity.node.revision.unpublish', $route_params),
          ];
        }
        else {
          $last_cell['#links']['publish'] = [
            'title' => $this->t('Publish'),
            'url' => Url::fromRoute('entity.node.revision.publish', $route_params),
          ];
        }

        if ($delete_permission) {
          $last_cell['#links']['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('node.revision_delete_confirm', $route_params),
          ];
        }

        $last_cell['#links'] = array_filter([
          $last_cell['#links']['publish'] ?? NULL,
          $last_cell['#links']['unpublish'] ?? NULL,
          $last_cell['#links']['revert'] ?? NULL,
          $last_cell['#links']['delete'] ?? NULL,
        ]);
      }
    }

    $form['#attached']['library'][] = 'ncms_ui/revisions';
  }

}
