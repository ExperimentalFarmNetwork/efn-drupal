<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the form element custom properties.
 *
 * @group YamlForm
 */
class YamlFormElementCustomPropertiesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_ui', 'yamlform_test_custom_properties'];

  /**
   * Tests form element custom properties.
   */
  public function testCustomProperties() {
    // Create and login admin user.
    $admin_user = $this->drupalCreateUser([
      'administer yamlform',
    ]);
    $this->drupalLogin($admin_user);

    // Get YAML form storage.
    $yamlform_storage = \Drupal::entityTypeManager()->getStorage('yamlform');

    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $yamlform_storage->load('contact');

    // Set name element.
    $name_element = [
      '#type' => 'textfield',
      '#title' => 'Your Name',
      '#default_value' => '[yamlform-authenticated-user:display-name]',
      '#required' => TRUE,
    ];

    // Check that name element render array does not contain custom property
    // or data.
    $this->assertEqual($yamlform->getElementDecoded('name'), $name_element);

    // Check that name input does not contain custom data.
    $this->drupalGet('yamlform/contact');
    $this->assertRaw('<input data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Submit empty custom property and data.
    $edit = [
      'properties[custom_data]' => '',
    ];
    $this->drupalPostForm('admin/structure/yamlform/manage/contact/element/name/edit', $edit, t('Save'));

    // Get updated contact form.
    $yamlform_storage->resetCache();
    $yamlform = $yamlform_storage->load('contact');

    // Check that name element render array still does not contain custom
    // property or data.
    $this->assertEqual($yamlform->getElementDecoded('name'), $name_element);

    // Add custom property and data.
    $edit = [
      'properties[custom_data]' => 'custom-data',
    ];
    $this->drupalPostForm('admin/structure/yamlform/manage/contact/element/name/edit', $edit, t('Save'));

    // Get updated contact form.
    $yamlform_storage->resetCache();
    $yamlform = $yamlform_storage->load('contact');

    // Check that name element does contain custom property or data.
    $name_element += [
      '#custom_data' => 'custom-data',
    ];
    $this->assertEqual($yamlform->getElementDecoded('name'), $name_element);

    // Check that name input does contain custom data.
    $this->drupalGet('yamlform/contact');
    $this->assertRaw('<input data-custom="custom-data" data-drupal-selector="edit-name" type="text" id="edit-name" name="name" value="' . htmlentities($admin_user->label()) . '" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');
  }

}
