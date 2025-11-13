<?php

namespace Drupal\ncms_ui\Traits;

use Drupal\Component\Serialization\Json;

/**
 * Trait to help with modal links.
 */
trait ModalLinkTrait {

  /**
   * Get attributes for a modal link.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $title
   *   The title for the modal dialog.
   * @param string $dialog_class
   *   A class for the modal dialog.
   *
   * @return array
   *   An array with a attributes.
   */
  public function getModalAttributes($title, $dialog_class = NULL): array {
    return [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => '80%',
        'title' => $title,
        'dialogClass' => $dialog_class ?? 'node-confirm',
      ]),
    ];
  }

  /**
   * Get the options for a URL object that should open in a modal.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $title
   *   The title for the modal dialog.
   * @param string $dialog_class
   *   A class for the modal dialog.
   *
   * @return array
   *   An array with a single key attributes and an array as value.
   */
  public function getModalUrlOptions($title, $dialog_class = NULL): array {
    return ['attributes' => $this->getModalAttributes($title, $dialog_class)];
  }

}
