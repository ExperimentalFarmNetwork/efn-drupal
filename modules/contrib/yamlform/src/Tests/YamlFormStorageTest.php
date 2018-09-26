<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form storage tests.
 *
 * @group YamlForm
 */
class YamlFormStorageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform'];

  /**
   * Test form storage.
   *
   * @see \Drupal\yamlform\YamlFormEntityStorage::load
   */
  public function testStorageCaching() {
    /** @var \Drupal\yamlform\YamlFormEntityStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('yamlform');

    $yamlform = $storage->load('contact');
    $yamlform->cached = TRUE;

    // Check that load (single) has the custom 'cached' property.
    $this->assertEqual($yamlform->cached, $storage->load('contact')->cached);

    // Check that loadMultiple does not have the custom 'cached' property.
    // The below test will fail when and if
    // 'Issue #1885830: Enable static caching for config entities.'
    // is resolved.
    $this->assert(!isset($storage->loadMultiple(['contact'])->cached));
  }

}
