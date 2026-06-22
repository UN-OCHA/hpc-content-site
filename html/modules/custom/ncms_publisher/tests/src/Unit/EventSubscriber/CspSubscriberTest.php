<?php

namespace Drupal\Tests\ncms_publisher\Unit\EventSubscriber;

use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\ncms_publisher\EventSubscriber\CspSubscriber;
use Drupal\ncms_publisher\PublisherManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the publisher CSP event subscriber.
 */
#[Group('ncms_publisher')]
class CspSubscriberTest extends UnitTestCase {

  /**
   * Tests the subscribed CSP event.
   */
  public function testGetSubscribedEvents(): void {
    $this->assertSame([
      CspEvents::POLICY_ALTER => ['onCspPolicyAlter'],
    ], CspSubscriber::getSubscribedEvents());
  }

  /**
   * Tests appending publisher hosts to frame-ancestors.
   */
  public function testOnCspPolicyAlter(): void {
    $publisher_manager = $this->createMock(PublisherManager::class);
    $publisher_manager->method('getKnownHosts')->willReturn([
      'https://publisher.example.com',
      'https://preview.example.com',
    ]);

    $policy = new Csp();
    $policy->setDirective('frame-ancestors', ["'self'"]);
    $event = new PolicyAlterEvent($policy, new Response());

    $subscriber = new CspSubscriber($publisher_manager);
    $subscriber->onCspPolicyAlter($event);

    $this->assertSame([
      "'self'",
      'https://publisher.example.com',
      'https://preview.example.com',
    ], $policy->getDirective('frame-ancestors'));
  }

}
