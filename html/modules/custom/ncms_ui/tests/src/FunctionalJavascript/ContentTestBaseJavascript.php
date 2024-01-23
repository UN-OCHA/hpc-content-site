<?php

namespace Drupal\Tests\ncms_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
abstract class ContentTestBaseJavascript extends WebDriverTestBase {

  use ContentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'ncms_ui',
    'ncms_ui_test',
  ];

  /**
   * The profile to install as a basis for testing.
   *
   * Using the standard profile as this has a lot of additional configuration.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setUp();

    $this->setupContentSpaceStructure();
  }

  /**
   * Waits for jQuery to become ready and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->htmlOutput(NULL);
  }

  /**
   * Press a button in a modal dialog.
   *
   * Helper function to work around the fact that these can only be addressed
   * by their tag content.
   *
   * @param string $value
   *   The button label.
   */
  protected function pressModalButton($value) {
    $this->getSession()->getPage()->find('xpath', '//div[contains(@class, "ui-dialog")]//div[contains(@class, "ui-dialog-buttonpane")]//button[text()="' . $value . '"]')->click();
  }

}
