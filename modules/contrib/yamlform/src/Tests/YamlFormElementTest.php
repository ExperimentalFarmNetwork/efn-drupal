<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form elements.
 *
 * @group YamlForm
 */
class YamlFormElementTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['yamlform', 'yamlform_test'];

  /**
   * Test element settings.
   */
  public function testElements() {

    /**************************************************************************/
    // Allowed tags
    /**************************************************************************/

    // Check <b> tags is allowed.
    $this->drupalGet('yamlform/test_element_allowed_tags');
    $this->assertRaw('Hello <b>...Goodbye</b>');

    // Check custom <ignored> <tag> is allowed and <b> tag removed.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('elements.allowed_tags', 'ignored tag')
      ->save();
    $this->drupalGet('yamlform/test_element_allowed_tags');
    $this->assertRaw('Hello <ignored></tag>...Goodbye');

    // Restore admin tags.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('elements.allowed_tags', 'admin')
      ->save();
  }

}
