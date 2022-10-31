<?php

namespace Drupal\Tests\ncms_ui\ExistingSite;

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
    $this->drupalGet('<front>');
    // Disabled until we have content to test.
    /* $this->assertSession()->statusCodeEquals(200); */
    $this->assertSession()->pageTextContains('HPC Content Module');
  }

}
