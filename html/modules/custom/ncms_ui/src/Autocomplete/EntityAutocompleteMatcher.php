<?php

namespace Drupal\ncms_ui\Autocomplete;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityAutocompleteMatcher as EntityEntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ncms_ui\ContentManager;
use Drupal\ncms_ui\Entity\ContentSpaceAwareInterface;

/**
 * Matcher class to get autocompletion results for entity reference.
 */
class EntityAutocompleteMatcher extends EntityEntityAutocompleteMatcher {

  use StringTranslationTrait;

  /**
   * The ncms content manager service.
   *
   * @var \Drupal\ncms_ui\ContentManager
   */
  protected $contentManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\ncms_ui\ContentManager $content_manager
   *   The ncms content manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, ContentManager $content_manager, DateFormatterInterface $date_formatter, EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->selectionManager = $selection_manager;
    $this->contentManager = $content_manager;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {

    $matches = [];

    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = $this->entityTypeManager->getStorage($target_type)->load($entity_id);
          $entity = $this->entityRepository->getTranslationFromContext($entity);

          $content_space_label = NULL;
          if ($entity instanceof ContentSpaceAwareInterface) {
            $content_space = $entity->getContentSpace();
            if ($content_space && $content_space->id() != $this->contentManager->getCurrentContentSpace()) {
              $content_space_label = $content_space->label();
            }
          }

          $updated = NULL;
          if ($entity instanceof EntityChangedInterface) {
            $updated = new TranslatableMarkup('last updated @updated', [
              '@updated' => $this->dateFormatter->format($entity->getChangedTime(), 'short'),
            ]);
          }
          $meta_data = implode(', ', array_filter([
            $content_space_label,
            $updated,
          ]));

          $key = $label . ' (' . $entity_id . ')';
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $label = $label . ' [<i>' . $meta_data . '</i>]';
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
