<?php

namespace Drupal\ncms_ui\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribe to controller events to add classes to the diff output.
 */
class ControllerAlterSubscriber implements EventSubscriberInterface {

  /**
   * Alters the controller output.
   */
  public function onView(ViewEvent $event) {
    $request = $event->getRequest();
    $route = $request->attributes->get('_route');

    if ($route == 'diff.revisions_diff') {
      $build = $event->getControllerResult();
      if (is_array($build)) {
        $build['header']['#prefix'] = '<header class="diff-header content-width">';
        $build['controls']['#prefix'] = '<header class="diff-controls content-width">';

        $event->setControllerResult($build);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::VIEW][] = ['onView', 50];
    return $events;
  }

}
