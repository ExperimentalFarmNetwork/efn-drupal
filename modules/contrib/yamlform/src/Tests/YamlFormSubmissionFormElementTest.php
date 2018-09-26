<?php

namespace Drupal\yamlform\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Utility\YamlFormElementHelper;

/**
 * Tests for form submission form element.
 *
 * @group YamlForm
 */
class YamlFormSubmissionFormElementTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'block', 'filter', 'node', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests elements.
   */
  public function testElements() {
    global $base_path;

    /* Test #unique element property */

    $this->drupalLogin($this->adminFormUser);

    $yamlform_unique = YamlForm::load('test_element_unique');

    // Check element with #unique property only allows one unique 'value' to be
    // submitted.
    $sid = $this->postSubmission($yamlform_unique, [], t('Submit'));
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this form, or you need to use a different value.');
    $this->drupalPostForm('yamlform/test_element_unique', [], t('Submit'));
    $this->assertRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this form, or you need to use a different value.');

    // Check element with #unique can be updated.
    $this->drupalPostForm("admin/structure/yamlform/manage/test_element_unique/submission/$sid/edit", [], t('Save'));
    $this->assertNoRaw('The value <em class="placeholder">value</em> has already been submitted once for the <em class="placeholder">textfield</em> field. You may have already submitted this form, or you need to use a different value.');
    // @todo Determine why test_element_unique is not updating correctly during
    // testing.
    // $this->assertRaw('Submission updated in <em class="placeholder">Test: Element: Unique</em>.');

    /* Test invalid elements */

    // Check invalid elements .
    $this->drupalGet('yamlform/test_element_invalid');
    $this->assertRaw('Unable to display this form. Please contact the site administrator.');

    /* Test ignored properties */

    // Check ignored properties.
    $yamlform_ignored_properties = YamlForm::load('test_element_ignored_properties');
    $elements = $yamlform_ignored_properties->getElementsInitialized();
    foreach (YamlFormElementHelper::$ignoredProperties as $ignored_property) {
      $this->assert(!isset($elements['test'][$ignored_property]), new FormattableMarkup('@property ignored.', ['@property' => $ignored_property]));
    }

    /* Test #autocomplete_items element property */

    // Check routes data-drupal-selector.
    $this->drupalGet('yamlform/test_element_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-items" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'yamlform/test_element_autocomplete/autocomplete/autocomplete_items" type="text" id="edit-autocomplete-items" name="autocomplete_items" value="" size="60" maxlength="255" />');

    // Check #autocomplete_items partial match.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'United']]);
    $this->assertRaw('[{"value":"United Arab Emirates","label":"United Arab Emirates"},{"value":"United Kingdom","label":"United Kingdom"},{"value":"United States","label":"United States"}]');

    // Check #autocomplete_items exact match.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'United States']]);
    $this->assertRaw('[{"value":"United States","label":"United States"}]');

    // Check #autocomplete_items just one character.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_items', ['query' => ['q' => 'U']]);
    $this->assertRaw('[{"value":"Anguilla","label":"Anguilla"},{"value":"Antigua and Barbuda","label":"Antigua and Barbuda"},{"value":"Aruba","label":"Aruba"},{"value":"Australia","label":"Australia"},{"value":"Austria","label":"Austria"}]');

    /* Test #autocomplete_existing element property */

    // Check autocomplete is not enabled until there is a submission.
    $this->drupalGet('yamlform/test_element_autocomplete');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'yamlform/test_element_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text" />');

    // Check #autocomplete_existing no match.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertRaw('[]');

    // Add #autocomplete_existing values to the submission table.
    $this->drupalPostForm('yamlform/test_element_autocomplete', ['autocomplete_existing' => 'abcdefg'], t('Submit'));

    // Check #autocomplete_existing enabled now that there is submission.
    $this->drupalGet('yamlform/test_element_autocomplete');
    $this->assertRaw('<input data-drupal-selector="edit-autocomplete-existing" class="form-autocomplete form-text" data-autocomplete-path="' . $base_path . 'yamlform/test_element_autocomplete/autocomplete/autocomplete_existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" />');
    $this->assertNoRaw('<input data-drupal-selector="edit-autocomplete-existing" type="text" id="edit-autocomplete-existing" name="autocomplete_existing" value="" size="60" maxlength="255" class="form-text" />');

    // Check #autocomplete_existing match.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'abc']]);
    $this->assertNoRaw('[]');
    $this->assertRaw('[{"value":"abcdefg","label":"abcdefg"}]');

    // Check #autocomplete_existing minimum number of characters < 3.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_existing', ['query' => ['q' => 'ab']]);
    $this->assertRaw('[]');
    $this->assertNoRaw('[{"value":"abcdefg","label":"abcdefg"}]');

    /* Test #autocomplete_existing and #autocomplete_items element property */

    // Add #autocomplete_body values to the submission table.
    $this->drupalPostForm('yamlform/test_element_autocomplete', ['autocomplete_both' => 'Existing Item'], t('Submit'));

    // Check #autocomplete_both match.
    $this->drupalGet('yamlform/test_element_autocomplete/autocomplete/autocomplete_both', ['query' => ['q' => 'Item']]);
    $this->assertNoRaw('[]');
    $this->assertRaw('[{"value":"Example Item","label":"Example Item"},{"value":"Existing Item","label":"Existing Item"}]');

    /* Test entity_autocomplete element */

    // Check 'entity_autocomplete' #default_value.
    $yamlform_entity_autocomplete = YamlForm::load('test_element_entity_reference');

    $this->drupalGet('yamlform/test_element_entity_reference');
    $this->assertFieldByName('entity_autocomplete_user_default', 'admin (1)');

    // Issue #2471154 Anonymous user label can't be viewed and auth user labels
    // are only accessible with 'access user profiles' permission.
    // https://www.drupal.org/node/2471154
    // Check if 'view label' access for accounts is supported (8.2.x+).
    if (User::load(0)->access('view label')) {
      $this->assertFieldByName('entity_autocomplete_user_tags', 'Anonymous (0), admin (1)');
    }
    else {
      $this->assertFieldByName('entity_autocomplete_user_tags', '- Restricted access - (0), admin (1)');
    }

    $form = $yamlform_entity_autocomplete->getSubmissionForm();

    // Single entity (w/o #tags).
    // TODO: (TESTING) Figure out why the below #default_value is an array when it should be the entity.
    // @see \Drupal\yamlform\YamlFormSubmissionForm::prepareElements()
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_default']['#default_value'][0] instanceof AccountInterface, 'user #default_value instance of \Drupal\Core\Session\AccountInterface.');

    // Multiple entities (w #tags).
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_tags']['#default_value'][0] instanceof AccountInterface, 'users #default_value instance of \Drupal\Core\Session\AccountInterface.');
    $this->assert($form['elements']['entity_autocomplete']['entity_autocomplete_user_tags']['#default_value'][1] instanceof AccountInterface, 'users #default_value instance of \Drupal\Core\Session\AccountInterface.');

    /* Test text format element */

    $yamlform_text_format = YamlForm::load('test_element_text_format');

    // Check 'text_format' values.
    $this->drupalGet('yamlform/test_element_text_format');
    $this->assertFieldByName('text_format[value]', 'The quick brown fox jumped over the lazy dog.');
    $this->assertRaw('No HTML tags allowed.');

    $text_format = [
      'value' => 'Custom value',
      'format' => 'custom_format',
    ];
    $form = $yamlform_text_format->getSubmissionForm(['data' => ['text_format' => $text_format]]);
    $this->assertEqual($form['elements']['text_format']['#default_value'], $text_format['value']);
    $this->assertEqual($form['elements']['text_format']['#format'], $text_format['format']);

    /* Test form properties */

    // Check element's root properties moved to the form's properties.
    $this->drupalGet('yamlform/test_form_properties');
    $this->assertPattern('/Form prefix<form /');
    $this->assertPattern('/<\/form>\s+Form suffix/');
    $this->assertRaw('<form class="yamlform-submission-test-form-properties-form yamlform-submission-form test-form-properties yamlform-details-toggle" invalid="invalid" style="border: 10px solid red; padding: 1em;" data-drupal-selector="yamlform-submission-test-form-properties-form" action="https://www.google.com/search" method="get" id="yamlform-submission-test-form-properties-form" accept-charset="UTF-8">');

    // Check editing form settings style attributes and custom properties
    // updates the element's root properties.
    $this->drupalLogin($this->adminFormUser);
    $edit = [
      'attributes[class][select][]' => ['form--inline clearfix', '_other_'],
      'attributes[class][other]' => 'test-form-properties',
      'attributes[style]' => 'border: 10px solid green; padding: 1em;',
      'attributes[attributes]' => '',
      'method' => '',
      'action' => '',
      'custom' => "'suffix': 'Form suffix TEST'
'prefix': 'Form prefix TEST'",
    ];
    $this->drupalPostForm('/admin/structure/yamlform/manage/test_form_properties/settings', $edit, t('Save'));
    $this->drupalGet('yamlform/test_form_properties');
    $this->assertPattern('/Form prefix TEST<form /');
    $this->assertPattern('/<\/form>\s+Form suffix TEST/');
    $this->assertRaw('<form class="yamlform-submission-test-form-properties-form yamlform-submission-form form--inline clearfix test-form-properties yamlform-details-toggle" style="border: 10px solid green; padding: 1em;" data-drupal-selector="yamlform-submission-test-form-properties-form" action="' . $base_path . 'yamlform/test_form_properties" method="post" id="yamlform-submission-test-form-properties-form" accept-charset="UTF-8">');

    /* Test form buttons */

    $this->drupalGet('yamlform/test_form_buttons');

    // Check draft button.
    $this->assertRaw('<input class="draft_button_attributes yamlform-button--draft button js-form-submit form-submit" style="color: blue" data-drupal-selector="edit-draft" type="submit" id="edit-draft" name="op" value="Save Draft" />');
    // Check next button.
    $this->assertRaw('<input class="wizard_next_button_attributes yamlform-button--next button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-next" type="submit" id="edit-next" name="op" value="Next Page &gt;" />');

    $this->drupalPostForm('yamlform/test_form_buttons', [], t('Next Page >'));

    // Check previous button.
    $this->assertRaw('<input class="wizard_prev_button_attributes js-yamlform-novalidate yamlform-button--previous button js-form-submit form-submit" style="color: yellow" data-drupal-selector="edit-previous" type="submit" id="edit-previous" name="op" value="&lt; Previous Page" />');
    // Check preview button.
    $this->assertRaw('<input class="preview_next_button_attributes yamlform-button--preview button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-next" type="submit" id="edit-next" name="op" value="Preview" />');

    $this->drupalPostForm(NULL, [], t('Preview'));

    // Check previous button.
    $this->assertRaw('<input class="preview_prev_button_attributes js-yamlform-novalidate yamlform-button--previous button js-form-submit form-submit" style="color: orange" data-drupal-selector="edit-previous" type="submit" id="edit-previous" name="op" value="&lt; Previous" />');
    // Check submit button.
    $this->assertRaw('<input class="form_submit_attributes yamlform-button--submit button button--primary js-form-submit form-submit" style="color: green" data-drupal-selector="edit-submit" type="submit" id="edit-submit" name="op" value="Submit" />');
  }

}
