<?php

namespace Drupal\ncms_publisher\EventSubscriber;

use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\ncms_publisher\PublisherManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policy to allow frame embedding for all publishers.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * The publisher manager service.
   *
   * @var \Drupal\ncms_publisher\PublisherManager
   */
  protected $publisherManager;

  /**
   * Constructor.
   *
   * @param \Drupal\ncms_publisher\PublisherManager $publisher_manager
   *   The publisher manager service.
   */
  public function __construct(PublisherManager $publisher_manager) {
    $this->publisherManager = $publisher_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    return $events;
  }

  /**
   * Alter CSP policy for frame-ancestor for every publisher.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent) {
    $policy = $alterEvent->getPolicy();
    $frame_ancestors = $policy->getDirective('frame-ancestors');
    $hosts = $this->publisherManager->getKnownHosts();
    foreach ($hosts as $host) {
      $frame_ancestors[] = $host;
    }
    $policy->setDirective('frame-ancestors', $frame_ancestors);
  }

}
