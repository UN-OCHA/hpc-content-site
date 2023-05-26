<?php

namespace Drupal\Tests\ncms_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ncms_ui\Traits\ContentTestTrait;

/**
 * Tests access based on content spaces.
 *
 * @group ncms_ui
 */
abstract class ContentTestBase extends BrowserTestBase {

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
  protected $defaultTheme = 'stark';

}
