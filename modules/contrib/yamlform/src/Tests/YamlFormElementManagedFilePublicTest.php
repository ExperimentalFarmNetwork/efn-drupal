<?php

namespace Drupal\yamlform\Tests;

/**
 * Test for form element managed public file handling (DRUPAL-PSA-2016-003).
 *
 * @see https://www.drupal.org/psa-2016-003
 *
 * @group YamlForm
 */
class YamlFormElementManagedFilePublicTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'file', 'yamlform', 'yamlform_test', 'yamlform_ui'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set public file upload support for testing.
    $settings_config = \Drupal::configFactory()->getEditable('yamlform.settings');
    $settings_config->set('file.file_public', TRUE);
    $settings_config->save();
  }

  /**
   * Test public upload protection.
   */
  public function testPublicUpload() {
    // Check status report private file system warning.
    $requirements = yamlform_requirements('runtime');
    $this->assertEqual($requirements['yamlform_file_private']['value'], (string) t('Private file system is set.'));

    $this->drupalLogin($this->adminFormUser);

    // Check element form warning message for public files.
    $this->drupalGet('admin/structure/yamlform/manage/test_element_managed_file/element/managed_file_single/edit');
    $this->assertRaw('Public files upload destination is dangerous for forms that are available to anonymous and/or untrusted users.');
    $this->assertFieldById('edit-properties-uri-scheme-public');

    // Check element form warning message not visible public files.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('file.file_public', FALSE)
      ->save();
    $this->drupalGet('admin/structure/yamlform/manage/test_element_managed_file/element/managed_file_single/edit');
    $this->assertNoRaw('Public files upload destination is dangerous for forms that are available to anonymous and/or untrusted users.');
    $this->assertNoFieldById('edit-properties-uri-scheme-public');

    // NOTE: Unable to test private file upload warning because SimpleTest
    // automatically enables private file uploads.

    // Check managed_file element is enabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_element_managed_file/element/add');
    $this->assertRaw('<td><div class="yamlform-form-filter-text-source">Managed file</div></td>');

    // Disable managed file element.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('elements.excluded_types.managed_file', 'managed_file')
      ->save();

    // Check disabled managed_file element remove from add element dialog.
    $this->drupalGet('admin/structure/yamlform/manage/test_element_managed_file/element/add');
    $this->assertNoRaw('<td><div class="yamlform-form-filter-text-source">Managed file</div></td>');

    // Check disabled managed_file element warning.
    $this->drupalGet('admin/structure/yamlform/manage/test_element_managed_file');
    $this->assertRaw('<em class="placeholder">managed_file (single)</em> is a <em class="placeholder">Managed file</em> element, which has been disabled and will not be rendered.');
    $this->assertRaw('<em class="placeholder">managed_file (multiple)</em> is a <em class="placeholder">Managed file</em> element, which has been disabled and will not be rendered.');
  }

}
