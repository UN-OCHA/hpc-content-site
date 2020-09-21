<?php

namespace Drupal\Tests\gho_general\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Test homepage for anonymous.
 */
class HomePageTest extends ExistingSiteBase {

  /**
   * Test home.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testHomePage() {
    $this->drupalGet('/');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Global Humanitarian Overview');
  }

}
