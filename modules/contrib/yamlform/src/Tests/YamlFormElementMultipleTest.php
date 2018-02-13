<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form element multiple.
 *
 * @group YamlForm
 */
class YamlFormElementMultipleTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests building of list elements.
   */
  public function test() {
    global $base_path;

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('yamlform/test_element_multiple', [], t('Submit'));
    $this->assertRaw("yamlform_multiple_default:
  - One
  - Two
  - Three");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('yamlform/test_element_multiple');

    // Check first tr.
    $this->assertRaw('<tr class="draggable odd" data-drupal-selector="edit-yamlform-multiple-default-items-0">');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-textfield form-type-textfield js-form-item-yamlform-multiple-default-items-0--item- form-item-yamlform-multiple-default-items-0--item- form-no-label">');
    $this->assertRaw('<label for="edit-yamlform-multiple-default-items-0-item-" class="visually-hidden">Item value</label>');
    $this->assertRaw('<input data-drupal-selector="edit-yamlform-multiple-default-items-0-item-" type="text" id="edit-yamlform-multiple-default-items-0-item-" name="yamlform_multiple_default[items][0][_item_]" value="One" size="60" maxlength="128" placeholder="Enter value" class="form-text" />');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-number form-type-number js-form-item-yamlform-multiple-default-items-0-weight form-item-yamlform-multiple-default-items-0-weight form-no-label">');
    $this->assertRaw('<label for="edit-yamlform-multiple-default-items-0-weight" class="visually-hidden">Item weight</label>');
    $this->assertRaw('<input class="yamlform-multiple-sort-weight form-number" data-drupal-selector="edit-yamlform-multiple-default-items-0-weight" type="number" id="edit-yamlform-multiple-default-items-0-weight" name="yamlform_multiple_default[items][0][weight]" value="0" step="1" size="10" />');
    $this->assertRaw('<td><input data-drupal-selector="edit-yamlform-multiple-default-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-yamlform-multiple-default-items-0-operations-add" name="yamlform_multiple_default_table_add_0" src="' . $base_path . 'core/misc/icons/787878/plus.svg" class="image-button js-form-submit form-submit" />');
    $this->assertRaw('<input data-drupal-selector="edit-yamlform-multiple-default-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-yamlform-multiple-default-items-0-operations-remove" name="yamlform_multiple_default_table_remove_0" src="' . $base_path . 'core/misc/icons/787878/ex.svg" class="image-button js-form-submit form-submit" />');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check populated 'yamlform_multiple_default'.
    $this->assertFieldByName('yamlform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('yamlform_multiple_default[items][1][_item_]', 'Two');
    $this->assertFieldByName('yamlform_multiple_default[items][2][_item_]', 'Three');
    $this->assertFieldByName('yamlform_multiple_default[items][3][_item_]', '');
    $this->assertNoFieldByName('yamlform_multiple_default[items][4][_item_]', '');

    // Check adding 'four' and 1 more option.
    $edit = [
      'yamlform_multiple_default[items][3][_item_]' => 'Four',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'yamlform_multiple_default_table_add');
    $this->assertFieldByName('yamlform_multiple_default[items][3][_item_]', 'Four');
    $this->assertFieldByName('yamlform_multiple_default[items][4][_item_]', '');

    // Check add 10 more rows.
    $edit = ['yamlform_multiple_default[add][more_items]' => 10];
    $this->drupalPostAjaxForm(NULL, $edit, 'yamlform_multiple_default_table_add');
    $this->assertFieldByName('yamlform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('yamlform_multiple_default[items][15][_item_]', '');

    // Check remove 'one' options.
    $this->drupalPostAjaxForm(NULL, $edit, 'yamlform_multiple_default_table_remove_0');
    $this->assertNoFieldByName('yamlform_multiple_default[items][14][_item_]', '');
    $this->assertNoFieldByName('yamlform_multiple_default[items][0][_item_]', 'One');
    $this->assertFieldByName('yamlform_multiple_default[items][0][_item_]', 'Two');
    $this->assertFieldByName('yamlform_multiple_default[items][1][_item_]', 'Three');
    $this->assertFieldByName('yamlform_multiple_default[items][2][_item_]', 'Four');
  }

}
