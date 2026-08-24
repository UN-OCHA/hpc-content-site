<?php

namespace Drupal\ncms_ui\Autocomplete;

use Drupal\system\Controller\EntityAutocompleteController as ControllerEntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller class for entity autocomplete requests.
 */
class EntityAutocompleteController extends ControllerEntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->matcher = $container->get('ncms_ui.autocomplete_matcher');
    return $instance;
  }

}
