<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for form (render) CodeMirror element .
 *
 * @group YamlForm
 */
class YamlFormElementCodeMirrorTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'yamlform', 'yamlform_test'];

  /**
   * Tests CodeMirror element.
   */
  public function test() {
    $this->drupalGet('yamlform/test_element_codemirror');

    /**************************************************************************/
    // code:yaml
    /**************************************************************************/

    // Check YAML.
    $this->assertRaw('<label for="edit-yaml-basic">YAML basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-yaml-basic" class="js-yamlform-codemirror yamlform-codemirror yaml form-textarea resize-vertical" data-yamlform-codemirror-mode="text/x-yaml" id="edit-yaml-basic" name="yaml_basic" rows="5" cols="60"></textarea>');

    /**************************************************************************/
    // code:html
    /**************************************************************************/

    // Check HTML.
    $this->assertRaw('<label for="edit-html-basic">HTML basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-html-basic" class="js-yamlform-codemirror yamlform-codemirror html form-textarea resize-vertical" data-yamlform-codemirror-mode="text/html" id="edit-html-basic" name="html_basic" rows="5" cols="60"></textarea>');

    /**************************************************************************/
    // code:text
    /**************************************************************************/

    // Check Text.
    $this->assertRaw('<label for="edit-text-basic">Text basic</label>');
    $this->assertRaw('<textarea data-drupal-selector="edit-text-basic" class="js-yamlform-codemirror yamlform-codemirror text form-textarea resize-vertical" data-yamlform-codemirror-mode="text/plain" id="edit-text-basic" name="text_basic" rows="5" cols="60"></textarea>');

    /**************************************************************************/
    // code:yaml
    /**************************************************************************/

    // Check associative array as the #default_value.
    $this->drupalPostForm('yamlform/test_element_codemirror', [], t('Submit'));
    $this->assertRaw('yaml_array:
  one: One
  two: Two
  three: Three');

    // Check invalid YAML.
    $edit = [
      'yaml_basic' => "'not: valid",
    ];
    $this->drupalPostForm('yamlform/test_element_codemirror', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">YAML basic</em> is not valid.');

    // Check valid YAML.
    $edit = [
      'yaml_basic' => 'is: valid',
    ];
    $this->drupalPostForm('yamlform/test_element_codemirror', $edit, t('Submit'));
    $this->assertNoRaw('<em class="placeholder">YAML basic</em> is not valid.');

    /**************************************************************************/
    // code:html
    /**************************************************************************/

    // Check invalid HTML.
    $edit = [
      'html_basic' => "<b>bold</bold>",
    ];
    $this->drupalPostForm('yamlform/test_element_codemirror', $edit, t('Submit'));
    $this->assertRaw('<em class="placeholder">HTML basic</em> is not valid.');
    $this->assertRaw('expected &#039;&gt;&#039;');

    // Check valid HTML.
    $edit = [
      'html_basic' => '<b>bold</b>',
    ];
    $this->drupalPostForm('yamlform/test_element_codemirror', $edit, t('Submit'));
    $this->assertNoRaw('<em class="placeholder">HTML basic</em> is not valid.');
    $this->assertNoRaw('expected &#039;&gt;&#039;');
  }

}
