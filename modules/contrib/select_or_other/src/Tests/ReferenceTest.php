<?php

/**
 * @file
 * Contains \Drupal\select_or_other\Tests\ReferenceTest.
 */

namespace Drupal\select_or_other\Tests;

/**
 * Tests the the functionality of the Reference widget.
 *
 * @codeCoverageIgnore
 *   Our unit tests do not have to cover the integration tests.
 *
 * @group 'Select or other'
 */
class ReferenceTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'taxonomy', 'select_or_other'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $field_settings = ['target_type' => 'taxonomy_term'];
    $widget = 'select_or_other_reference';
    $widgets = ['select_or_other_select', 'select_or_other_buttons'];
    $this->prepareTestFields('entity_reference', $field_settings, $widget, $widgets);
    $user = $this->drupalCreateUser($this->defaultPermissions);
    $this->drupalLogin($user);
  }

  /**
   * Make sure an empty option is present when relevant.
   */
  public function testEmptyOption($empty_option = '') {
    parent::testEmptyOption('My cool new value');
  }

}
