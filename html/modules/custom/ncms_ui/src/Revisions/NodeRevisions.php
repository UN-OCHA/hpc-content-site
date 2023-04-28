<?php

namespace Drupal\ncms_ui\Revisions;

use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Controller class for special logic on node revisions.
 */
class NodeRevisions {

  use StringTranslationTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * NodeRevisions constructor.
   *
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The entity type manager service.
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * Alter the node revisions overview table.
   *
   * @param array $build
   *   The already preprocessed table structure.
   */
  public function alterRevisionsOverviewTable(&$build) {
    $header = &$build['header'];
    $rows = &$build['rows'];

    // Modify the columns headers.
    unset($header['revision']);
    $header = array_merge([
      [
        'tag' => 'th',
        'content' => $this->t('Version'),
      ],
      [
        'tag' => 'th',
        'content' => $this->t('User'),
      ],
      [
        'tag' => 'th',
        'content' => $this->t('Created'),
      ],
      [
        'tag' => 'th',
        'content' => $this->t('Status'),
      ],
      [
        'tag' => 'th',
        'content' => $this->t('Message'),
      ],
    ], $header);

    // Now modify the rows.
    foreach ($rows as $key => &$row) {
      $cells = &$row['cells'];
      $context = $cells[0]['content']['#context'];
      unset($cells[0]);

      $message = $this->renderer->renderPlain($context['message']);
      $message_parts = explode(' (', $message);
      $status = trim(array_pop($message_parts), ')');

      $cells = array_merge([
        [
          'tag' => 'td',
          'content' => count($rows) - $key,
        ],
        [
          'tag' => 'td',
          'content' => $context['username'],
        ],
        [
          'tag' => 'td',
          'content' => $context['date'],
        ],
        [
          'tag' => 'td',
          'content' => $status,
        ],
        [
          'tag' => 'td',
          'content' => implode(' ', $message_parts),
        ],
      ], $cells);
      $last_cell = &$cells[count($cells) - 1]['content'];
      if (($last_cell['#markup'] ?? NULL) == $this->t('Current revision')) {
        $last_cell['#markup'] = $this->t('Current version');
      }
      else {
        // unset($last_cell['#links'])
      }
    }
  }

}
