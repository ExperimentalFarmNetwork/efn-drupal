<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form element options.
 *
 * @group YamlForm
 */
class YamlFormElementOptionsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests building of options elements.
   */
  public function test() {
    global $base_path;

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('yamlform/test_element_options', [], t('Submit'));
    $this->assertRaw("yamlform_options: {  }
yamlform_options_default_value:
  one: One
  two: Two
  three: Three
yamlform_options_optgroup:
  'Group One':
    one: One
  'Group Two':
    two: Two
  'Group Three':
    three: Three
yamlform_element_options_entity: yes_no
yamlform_element_options_custom:
  one: One
  two: Two
  three: Three");

    // Check default value handling.
    $this->drupalPostForm('yamlform/test_element_options', ['yamlform_element_options_custom[options]' => 'yes_no'], t('Submit'));
    $this->assertRaw("yamlform_element_options_custom: yes_no");
  }

}
