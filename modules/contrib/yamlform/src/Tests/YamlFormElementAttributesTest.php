<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form element attributes.
 *
 * @group YamlForm
 */
class YamlFormElementAttributesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests element attributes.
   */
  public function test() {
    // Check default value handling.
    $this->drupalPostForm('yamlform/test_element_attributes', [], t('Submit'));
    $this->assertRaw("yamlform_element_attributes:
  class:
    - one
    - two
    - four
  style: 'color: red'
  custom: test");
  }

}
