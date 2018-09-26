<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for the form handler plugin.
 *
 * @group YamlForm
 */
class YamlFormHandlerPluginTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['yamlform', 'yamlform_devel'];

  /**
   * Tests form element plugin.
   */
  public function testYamlFormHandler() {
    $yamlform = YamlForm::load('contact');

    // Check initial dependencies.
    $this->assertEqual($yamlform->getDependencies(), ['module' => ['yamlform']]);

    // Add 'debug' handler provided by the yamlform_devel.module.
    $yamlform_handler_configuration = [
      'id' => 'debug',
      'label' => 'Debug',
      'handler_id' => 'debug',
      'status' => 1,
      'weight' => 2,
      'settings' => [],
    ];
    $yamlform->addYamlFormHandler($yamlform_handler_configuration);
    $yamlform->save();

    // Check that handler has been added to the dependencies.
    $this->assertEqual($yamlform->getDependencies(), ['module' => ['yamlform_devel', 'yamlform']]);

    // Uninstall the yamlform_devel.module which will also remove the
    // debug handler.
    $this->container->get('module_installer')->uninstall(['yamlform_devel']);
    $yamlform = YamlForm::load('contact');

    // Check that handler was removed from the dependencies.
    $this->assertNotEqual($yamlform->getDependencies(), ['module' => ['yamlform_devel', 'yamlform']]);
    $this->assertEqual($yamlform->getDependencies(), ['module' => ['yamlform']]);
  }

}
