<?php

namespace Drupal\ncms_ui\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
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
use Drupal\diff\DiffLayoutManager;
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
   * The field diff layout plugin manager service.
   *
   * @var \Drupal\diff\DiffLayoutManager
   */
  protected $diffLayoutManager;

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
   * Set the diff layout manager service.
   *
   * @param \Drupal\diff\DiffLayoutManager $diff_layout_manager
   *   The diff layout manager service.
   */
  public function setDiffLayoutManager(DiffLayoutManager $diff_layout_manager) {
    $this->diffLayoutManager = $diff_layout_manager;
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
    $rev_revert_perm = $account->hasPermission("revert $type revisions") ||
      $account->hasPermission('revert all revisions') ||
      $account->hasPermission('administer nodes');
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

    /** @var \Drupal\ncms_ui\Entity\ContentInterface[] $revisions */
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

      $row_classes = array_merge($row['#attributes']['class'] ?? [], [Html::getClass($revision->getVersionStatusLabel())]);
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
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ],
        'created' => $link->toRenderable(),
        'status' => [
          '#type' => 'markup',
          '#markup' => $revision->getVersionStatusLabel(),
        ],
      ] + $row;

      if ($log_message = $revision->getRevisionLogMessage()) {
        $row['status'] = [
          $row['status'],
          [
            '#theme' => 'tooltip',
            '#content' => strip_tags($log_message),
            '#icon' => 'info',
          ],
        ];
      }

      $route_params = [
        'node' => $revision->id(),
        'node_revision' => $revision->getRevisionId(),
      ];

      $last_cell = &$row['operations'];
      unset($last_cell['#links']['delete']);
      if ($revision->isDefaultRevision() && $rev_revert_perm) {
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
          'publish' => $last_cell['#links']['publish'] ?? NULL,
          'unpublish' => $last_cell['#links']['unpublish'] ?? NULL,
          'revert' => $last_cell['#links']['revert'] ?? NULL,
          'delete' => $last_cell['#links']['delete'] ?? NULL,
        ]);

        if (!empty($last_cell['#links']['revert'])) {
          $attributes = [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'title' => $this->t('Revert to version #@version', [
                '@version' => $revision->getVersionId(),
              ]),
              'width' => '350px',
            ]),
          ];
          $last_cell['#links']['revert']['attributes'] = $attributes;
        }
      }
    }

    $form['#attached']['library'][] = 'ncms_ui/revisions';

    if ($this->diffLayoutManager) {
      $ajax = [
        'callback' => [$this, 'openDiffModal'],
        'event' => 'click',
      ];
      $form['submit_top']['#ajax'] = $ajax;
      $form['submit_bottom']['#ajax'] = $ajax;
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }
  }

  /**
   * Open the diff page in a modal.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An ajax response object.
   */
  public function openDiffModal(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $input = $form_state->getUserInput();
    $vid_left = $input['radios_left'];
    $vid_right = $input['radios_right'];
    $nid = $input['nid'];
    $entity = $this->entityTypeManager->getStorage('node')->load($nid);

    // Always place the older revision on the left side of the comparison
    // and the newer revision on the right side (however revisions can be
    // compared both ways if we manually change the order of the parameters).
    if ($vid_left > $vid_right) {
      $aux = $vid_left;
      $vid_left = $vid_right;
      $vid_right = $aux;
    }

    $url = Url::fromRoute('diff.revisions_diff', [
      'node' => $nid,
      'left_revision' => $vid_left,
      'right_revision' => $vid_right,
      'filter' => $this->diffLayoutManager->getDefaultLayout(),
    ], [
      'query' => [
        'view_mode' => 'full',
      ],
    ]);

    $title = $this->t('Changes to %title', ['%title' => $entity->label()]);

    // Iframe dimensions. The height is set initially, but is adjusted in the
    // client.
    $max_width = '100%';
    $max_height = 800;
    $build = [
      '#type' => 'container',
      'iframe' => [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'src' => $url->toString(),
          'frameborder' => 0,
          'scrolling' => 'no',
          'allowtransparency' => TRUE,
          'width' => $max_width,
          'height' => $max_height,
          'class' => [],
          'id' => 'node-preview',
          // Add the page title, so that it can be set for the DOM document
          // via javascript once the iframe get's included.
          'data-page-title' => $title,
          // Adding this onload fixing formatting issues when printing from
          // Safari.
          'onload' => 'this.contentWindow.focus()',
        ],
      ],
      '#attached' => [
        'library' => [
          'ncms_ui/node_preview',
        ],
      ],
    ];

    $response->addCommand(new OpenModalDialogCommand($title, $build, [
      'width' => '80%',
      'dialogClass' => 'node-revision-diff',
    ]));
    return $response;
  }

}
