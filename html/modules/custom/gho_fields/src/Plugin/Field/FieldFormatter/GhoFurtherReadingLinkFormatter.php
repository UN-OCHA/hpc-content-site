<?php

namespace Drupal\gho_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Url;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'gho_further_reading_link' formatter.
 *
 * @FieldFormatter(
 *   id = "gho_further_reading_link",
 *   label = @Translation("GHO further reading link"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class GhoFurtherReadingLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Retrieve the source information which is stored as a link attribute.
      $source = '';
      if (isset($item->options['attributes']['source'])) {
        $source = $item->options['attributes']['source'];
      }

      $url = $this->buildUrl($item);
      $element[$delta] = [
        '#theme' => 'gho_further_reading_link_formatter',
        '#url' => $url,
        '#title' => $item->title ?: $url->toString(),
        '#source' => $source,
      ];
    }

    return $element;
  }

  /**
   * Builds the \Drupal\Core\Url object for a link field item.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link field item being rendered.
   *
   * @return \Drupal\Core\Url
   *   A Url object.
   */
  protected function buildUrl(LinkItemInterface $item) {
    $url = $item->getUrl() ?: Url::fromRoute('<none>');

    $options = $item->options;
    $options += $url->getOptions();

    // No need to have the source added as attribute to the link.
    unset($options['attributes']['source']);

    $url->setOptions($options);

    return $url;
  }

}
