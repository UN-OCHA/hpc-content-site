<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
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
    return $this->t('Preview: @title', [
      '@title' => $this->title($node),
    ]);
  }

  /**
   * Build the iframe view.
   *
   * Using an iframe to be able to use the full frontend styling from links in
   * the backend.
   */
  public function viewIframe(NodeInterface $node, $preview = FALSE) {

    // Iframe dimensions. The height is set initially, but is adjusted in the
    // client.
    $max_width = '100%';
    $max_height = 800;

    if ($preview) {
      $url = Url::fromRoute('entity.node.preview', [
        'node_preview' => $node->uuid(),
        'view_mode_id' => 'full',
      ]);
    }
    else {
      $url = Url::fromRoute('entity.node.standalone', ['node' => $node->id()]);
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
          'data-page-title' => $node->type->entity->label() . ' preview: ' . $node->label(),
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
    /** @var \Drupal\Core\Entity\ContentEntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $entity = $form_object->getEntity();

    $node_preview_controller = ViewController::create(\Drupal::getContainer());
    $title = $node_preview_controller->previewTitle($entity);
    $build = $node_preview_controller->viewIframe($entity, TRUE);

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($title, $build, [
      'width' => '80%',
      'dialogClass' => 'node-preview',
    ]));
    return $response;
  }

}
