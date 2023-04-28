<?php

namespace Drupal\ncms_ui\Autocomplete;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\system\Controller\EntityAutocompleteController as ControllerEntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for entity autocomplete requests.
 */
class EntityAutocompleteController extends ControllerEntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var \Drupal\ncms_ui\Autocomplete\EntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ncms_ui.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
