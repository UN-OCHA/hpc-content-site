<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ncms_ui\Entity\Content\ContentBase;
use Drupal\ncms_ui\Entity\ContentInterface;
use Drupal\ncms_ui\Entity\ContentVersionInterface;
use Drupal\ncms_ui\Entity\IframeDisplayContentInterface;
use Drupal\node\Controller\NodeViewController;
use Drupal\node\NodeInterface;

/**
 * Implementation of the ViewController class.
 */
class ViewController extends NodeViewController {

  use StringTranslationTrait;

  /**
   * Get the title of the preview.
   */
  public function previewTitle(NodeInterface $node) {
    if ($node instanceof ContentVersionInterface) {
      return $this->t('Preview: @title (@version)', [
        '@title' => $node->label(),
        '@version' => $node->getContentStatus() == ContentBase::CONTENT_STATUS_PUBLISHED ? $this->t('Latest published') : $this->t('Latest draft'),
      ]);
    }
    else {
      return $this->t('Preview: @title', [
        '@title' => $node->label(),
      ]);
    }
  }

  /**
   * Build the iframe view.
   *
   * Using an iframe to be able to use the full frontend styling from links in
   * the backend.
   */
  public function viewIframe(IframeDisplayContentInterface $node, ContentInterface $node_revision = NULL, $preview = FALSE) {
    // Iframe dimensions. The height is set initially, but is adjusted in the
    // client.
    $max_width = '100%';
    $max_height = 800;

    if ($preview) {
      $url = $node->getIframePreviewUrl();
    }
    elseif ($node_revision !== NULL) {
      $url = $node->getIframeStandaloneRevisionUrl();
    }
    else {
      $url = $node->getIframeStandaloneUrl();
    }

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
          'data-page-title' => $this->t('@type preview: @label', [
            '@type' => $node->getBundleLabel(),
            '@label' => $node->label(),
          ]),
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
    return $build;
  }

  /**
   * Form submission handler for the 'preview' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function previewModal(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object instanceof EntityFormInterface ? $form_object->getEntity() : NULL;
    if ($entity instanceof IframeDisplayContentInterface) {
      $node_preview_controller = ViewController::create(\Drupal::getContainer());
      $title = $node_preview_controller->previewTitle($entity);
      $build = $node_preview_controller->viewIframe($entity, NULL, TRUE);
      $response->addCommand(new OpenModalDialogCommand($title, $build, [
        'width' => '80%',
        'dialogClass' => 'node-preview',
      ]));
    }
    return $response;
  }

  /**
   * Custom access callback for canoncial node routes.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account that tries to access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If $condition is TRUE, isAllowed() will be TRUE, otherwise isNeutral()
   *   will be TRUE.
   */
  public function nodeCanonicalRouteAccess(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($account && $account->isAuthenticated() && $node->access('update', $account));
  }

}
