<?php

namespace Drupal\Tests\select_or_other\Functional;

/**
 * Tests the the functionality of the Reference widget.
 *
 * @group select_or_other
 */
class ListTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['options'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $field_settings = [];
    $widget = 'select_or_other_list';
    $widgets = ['select_or_other_select', 'select_or_other_buttons'];
    $this->prepareTestFields('list_string', $field_settings, $widget, $widgets);
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
