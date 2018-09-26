<?php

namespace Drupal\yamlform\Tests;

use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for table elements.
 *
 * @group YamlForm
 */
class YamlFormElementTableTest extends YamlFormTestBase {

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

    $yamlform = YamlForm::load('test_element_table');

    /**************************************************************************/
    // Table select sort.
    /**************************************************************************/

    // Check processing.
    $edit = [
      'yamlform_tableselect_sort_custom[one][weight]' => '4',
      'yamlform_tableselect_sort_custom[two][weight]' => '3',
      'yamlform_tableselect_sort_custom[three][weight]' => '2',
      'yamlform_tableselect_sort_custom[four][weight]' => '1',
      'yamlform_tableselect_sort_custom[five][weight]' => '0',
      'yamlform_tableselect_sort_custom[one][checkbox]' => TRUE,
      'yamlform_tableselect_sort_custom[two][checkbox]' => TRUE,
      'yamlform_tableselect_sort_custom[three][checkbox]' => TRUE,
      'yamlform_tableselect_sort_custom[four][checkbox]' => TRUE,
      'yamlform_tableselect_sort_custom[five][checkbox]' => TRUE,
    ];
    $this->drupalPostForm('yamlform/test_element_table', $edit, t('Submit'));
    $this->assertRaw("yamlform_tableselect_sort_custom:
  - five
  - four
  - three
  - two
  - one");

    /**************************************************************************/
    // Table sort.
    /**************************************************************************/

    // Check processing.
    $edit = [
      'yamlform_table_sort_custom[one][weight]' => '4',
      'yamlform_table_sort_custom[two][weight]' => '3',
      'yamlform_table_sort_custom[three][weight]' => '2',
      'yamlform_table_sort_custom[four][weight]' => '1',
      'yamlform_table_sort_custom[five][weight]' => '0',
    ];
    $this->drupalPostForm('yamlform/test_element_table', $edit, t('Submit'));
    $this->assertRaw("yamlform_table_sort_custom:
  - five
  - four
  - three
  - two
  - one");

    /**************************************************************************/
    // Export results.
    /**************************************************************************/

    $this->drupalLogin($this->adminFormUser);

    $excluded_columns = $this->getExportColumns($yamlform);
    unset($excluded_columns['yamlform_tableselect_sort_custom']);

    $this->getExport($yamlform, ['options_format' => 'separate', 'excluded_columns' => $excluded_columns]);
    $this->assertRaw('"yamlform_tableselect_sort (custom): one","yamlform_tableselect_sort (custom): two","yamlform_tableselect_sort (custom): three","yamlform_tableselect_sort (custom): four","yamlform_tableselect_sort (custom): five"');
    $this->assertRaw('5,4,3,2,1');
  }

}
