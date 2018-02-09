<?php

namespace Drupal\yamlform\Tests;

use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Entity\YamlFormSubmission;

/**
 * Tests for form submission form settings.
 *
 * @group YamlForm
 */
class YamlFormSubmissionFormSettingsTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'block', 'filter', 'node', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests form setting including confirmation.
   */
  public function testSettings() {
    // Login the admin user.
    $this->drupalLogin($this->adminFormUser);

    /* Test assets (CSS / JS) */

    $yamlform_assets = YamlForm::load('test_form_assets');

    // Check has CSS and JavaScript.
    $this->drupalGet('yamlform/test_form_assets');
    $this->assertRaw('<link rel="stylesheet" href="' . base_path() . 'yamlform/test_form_assets/assets/css?v=');
    $this->assertRaw('<script src="' . base_path() . 'yamlform/test_form_assets/assets/javascript?v=');

    // Clear CSS and JavaScript.
    $yamlform_assets->setCss('')->setJavaScript('')->save();

    // Check has no CSS or JavaScript.
    $this->drupalGet('yamlform/test_form_assets');
    $this->assertNoRaw('<link rel="stylesheet" href="' . base_path() . 'yamlform/test_form_assets/assets/css?v=');
    $this->assertNoRaw('<script src="' . base_path() . 'yamlform/test_form_assets/assets/javascript?v=');

    /* Test next_serial */

    $yamlform_contact = YamlForm::load('contact');

    // Set next serial to 99.
    $this->drupalPostForm('admin/structure/yamlform/manage/contact/settings', ['next_serial' => 99], t('Save'));

    // Check next serial is 99.
    $sid = $this->postSubmissionTest($yamlform_contact, [], t('Send message'));
    $yamlform_submission = YamlFormSubmission::load($sid);
    $this->assertEqual($yamlform_submission->serial(), 99);

    // Check that next serial is set to max serial.
    $this->drupalPostForm('admin/structure/yamlform/manage/contact/settings', ['next_serial' => 1], t('Save'));
    $this->assertRaw('The next submission number was increased to 100 to make it higher than existing submissions.');

    /* Test form closed (status=false) */

    $yamlform_closed = YamlForm::load('test_form_closed');

    $this->drupalLogout();

    // Check form closed message is displayed.
    $this->assertTrue($yamlform_closed->isClosed());
    $this->assertFalse($yamlform_closed->isOpen());
    $this->drupalGet('yamlform/test_form_closed');
    $this->assertNoRaw('This message should not be displayed)');
    $this->assertRaw('This form is closed.');

    // Check form closed message is displayed.
    $yamlform_closed->setSetting('form_closed_message', '');
    $yamlform_closed->save();
    $this->drupalGet('yamlform/test_form_closed');
    $this->assertNoRaw('This form is closed.');
    $this->assertRaw('Sorry...This form is closed to new submissions.');

    $this->drupalLogin($this->adminFormUser);

    // Check form is not closed for admins and warning is displayed.
    $this->drupalGet('yamlform/test_form_closed');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form is closed.');
    $this->assertRaw('Only submission administrators are allowed to access this form and create new submissions.');

    // Check form closed message is not displayed.
    $yamlform_closed->set('status', 1);
    $yamlform_closed->save();
    $this->assertFalse($yamlform_closed->isClosed());
    $this->assertTrue($yamlform_closed->isOpen());
    $this->drupalGet('yamlform/test_form_closed');
    $this->assertRaw('This message should not be displayed');
    $this->assertNoRaw('This form is closed.');
    $this->assertNoRaw('Only submission administrators are allowed to access this form and create new submissions.');

    /* Test form prepopulate (form_prepopulate) */

    $yamlform_prepopulate = YamlForm::load('test_form_prepopulate');

    // Check prepopulation of an element.
    $this->drupalGet('yamlform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => ['red', 'white']]]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    $this->drupalGet('yamlform/test_form_prepopulate', ['query' => ['name' => 'John', 'colors' => 'red']]);
    $this->assertFieldByName('name', 'John');
    $this->assertFieldChecked('edit-colors-red');
    $this->assertNoFieldChecked('edit-colors-white');
    $this->assertNoFieldChecked('edit-colors-blue');

    // Check disabling prepopulation of an element.
    $yamlform_prepopulate->setSetting('form_prepopulate', FALSE);
    $yamlform_prepopulate->save();
    $this->drupalGet('yamlform/test_form_prepopulate', ['query' => ['name' => 'John']]);
    $this->assertFieldByName('name', '');

    /* Test form prepopulate source entity (form_prepopulate_source_entity) */

    // Check prepopulating source entity.
    $this->drupalPostForm('yamlform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'yamlform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($yamlform_prepopulate);
    $submission = YamlFormSubmission::load($sid);
    $this->assertNotNull($submission->getSourceEntity());
    if ($submission->getSourceEntity()) {
      $this->assertEqual($submission->getSourceEntity()->getEntityTypeId(), 'yamlform');
      $this->assertEqual($submission->getSourceEntity()->id(), 'contact');
    }

    // Check disabling prepopulation source entity.
    $yamlform_prepopulate->setSetting('form_prepopulate_source_entity', FALSE);
    $yamlform_prepopulate->save();
    $this->drupalPostForm('yamlform/test_form_prepopulate', [], t('Submit'), ['query' => ['source_entity_type' => 'yamlform', 'source_entity_id' => 'contact']]);
    $sid = $this->getLastSubmissionId($yamlform_prepopulate);
    $submission = YamlFormSubmission::load($sid);
    $this->assert(!$submission->getSourceEntity());

    /* Test form disable back button (form_disable_back) */

    $yamlform_form_disable_back = YamlForm::load('test_form_disable_back');

    // Check form has yamlform.form.disable_back.js.
    $this->drupalGet('yamlform/test_form_disable_back');
    $this->assertRaw('yamlform.form.disable_back.js');

    // Disable YAML specific form_disable_back setting.
    $yamlform_form_disable_back->setSetting('form_disable_back', FALSE);
    $yamlform_form_disable_back->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_disable_back/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-disable-back" aria-describedby="edit-form-disable-back--description" type="checkbox" id="edit-form-disable-back" name="form_disable_back" value class="form-checkbox" />');

    // Check form no longer has yamlform.form.disable_back.js.
    $this->drupalGet('yamlform/test_form_disable_back');
    $this->assertNoRaw('yamlform.form.disable_back.js');

    // Enable default (global) disable_back on all forms.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('settings.default_form_disable_back', TRUE)
      ->save();

    // Check disable_back checkbox is disabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_disable_back/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-disable-back-disabled" aria-describedby="edit-form-disable-back-disabled--description" disabled="disabled" type="checkbox" id="edit-form-disable-back-disabled" name="form_disable_back_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Back button is disabled for all forms.');

    // Check form has yamlform.form.disable_back.js.
    $this->drupalGet('yamlform/test_form_disable_back');
    $this->assertRaw('yamlform.form.disable_back.js');

    /* Test form (client-side) unsaved (form_unsaved) */

    $yamlform_form_unsaved = YamlForm::load('test_form_unsaved');

    // Check form has .js-yamlform-unsaved class.
    $this->drupalGet('yamlform/test_form_unsaved');
    $this->assertCssSelect('form.js-yamlform-unsaved', t('Form has .js-yamlform-unsaved class.'));

    // Disable YAML specific form unsaved setting.
    $yamlform_form_unsaved->setSetting('form_unsaved', FALSE);
    $yamlform_form_unsaved->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_unsaved/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-unsaved" aria-describedby="edit-form-unsaved--description" type="checkbox" id="edit-form-unsaved" name="form_unsaved" value class="form-checkbox" />');

    // Check form no longer has .js-yamlform-unsaved class.
    $this->drupalGet('yamlform/test_form_novalidate');
    $this->assertNoCssSelect('form.js-yamlform-unsaved', t('Form does not have .js-yamlform-unsaved class.'));

    // Enable default (global) unsaved on all forms.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('settings.default_form_unsaved', TRUE)
      ->save();

    // Check unsaved checkbox is disabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_unsaved/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-unsaved-disabled" aria-describedby="edit-form-unsaved-disabled--description" disabled="disabled" type="checkbox" id="edit-form-unsaved-disabled" name="form_unsaved_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Unsaved warning is enabled for all forms.');

    // Check unsaved attribute added to form.
    $this->drupalGet('yamlform/test_form_unsaved');
    $this->assertCssSelect('form.js-yamlform-unsaved', t('Form has .js-yamlform-unsaved class.'));

    /* Test form (client-side) novalidate (form_novalidate) */

    $yamlform_form_novalidate = YamlForm::load('test_form_novalidate');

    // Check form has novalidate attribute.
    $this->drupalGet('yamlform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', t('Form has the proper novalidate attribute.'));

    // Disable YAML specific form client-side validation setting.
    $yamlform_form_novalidate->setSetting('form_novalidate', FALSE);
    $yamlform_form_novalidate->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_novalidate/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-novalidate" aria-describedby="edit-form-novalidate--description" type="checkbox" id="edit-form-novalidate" name="form_novalidate" value class="form-checkbox" />');
    $this->assertRaw('If checked, the <a href="http://www.w3schools.com/tags/att_form_novalidate.asp">novalidate</a> attribute, which disables client-side validation, will be added to this form.');

    // Check form no longer has novalidate attribute.
    $this->drupalGet('yamlform/test_form_novalidate');
    $this->assertNoCssSelect('form[novalidate="novalidate"]', t('Form have client-side validation enabled.'));

    // Enable default (global) disable client-side validation on all forms.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('settings.default_form_novalidate', TRUE)
      ->save();

    // Check novalidate checkbox is disabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_novalidate/settings');
    $this->assertNoRaw('If checked, the <a href="http://www.w3schools.com/tags/att_form_novalidate.asp">novalidate</a> attribute, which disables client-side validation, will be added to this form.');
    $this->assertRaw('<input data-drupal-selector="edit-form-novalidate-disabled" aria-describedby="edit-form-novalidate-disabled--description" disabled="disabled" type="checkbox" id="edit-form-novalidate-disabled" name="form_novalidate_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Client-side validation is disabled for all forms.');

    // Check novalidate attribute added to form.
    $this->drupalGet('yamlform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', t('Form has the proper novalidate attribute.'));

    /* Test form details toggle (form_details_toggle) */

    $yamlform_form_details_toggle = YamlForm::load('test_form_details_toggle');

    // Check form has .yamlform-details-toggle class.
    $this->drupalGet('yamlform/test_form_details_toggle');
    $this->assertCssSelect('form.yamlform-details-toggle', t('Form has the .yamlform-details-toggle class.'));

    // Check details toggle checkbox is disabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_details_toggle/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-details-toggle-disabled" aria-describedby="edit-form-details-toggle-disabled--description" disabled="disabled" type="checkbox" id="edit-form-details-toggle-disabled" name="form_details_toggle_disabled" value="1" checked="checked" class="form-checkbox" />');
    $this->assertRaw('Expand/collapse all (details) link is automatically added to all forms.');

    // Disable default (global) details toggle on all forms.
    \Drupal::configFactory()->getEditable('yamlform.settings')
      ->set('settings.default_form_details_toggle', FALSE)
      ->save();

    // Check .yamlform-details-toggle class still added to form.
    $this->drupalGet('yamlform/test_form_details_toggle');
    $this->assertCssSelect('form.yamlform-details-toggle', t('Form has the .yamlform-details-toggle class.'));

    // Check details toggle checkbox is enabled.
    $this->drupalGet('admin/structure/yamlform/manage/test_form_details_toggle/settings');
    $this->assertRaw('<input data-drupal-selector="edit-form-details-toggle" aria-describedby="edit-form-details-toggle--description" type="checkbox" id="edit-form-details-toggle" name="form_details_toggle" value checked="checked" class="form-checkbox" />');
    $this->assertRaw('If checked, an expand/collapse all (details) link will be added to this form when there are two or more details elements available on the form.');

    // Disable YAML specific form details toggle setting.
    $yamlform_form_details_toggle->setSetting('form_details_toggle', FALSE);
    $yamlform_form_details_toggle->save();

    // Check form does not hav .yamlform-details-toggle class.
    $this->drupalGet('yamlform/test_form_details_toggle');
    $this->assertNoCssSelect('form.yamlform-details-toggle', t('Form does not have the .yamlform-details-toggle class.'));

    /* Test autofocus (form_autofocus) */

    // Check form has autofocus class.
    $this->drupalGet('yamlform/test_form_autofocus');
    $this->assertCssSelect('.js-yamlform-autofocus');

    /* Test confidential submissions (form_confidential)*/

    // Check logout warning.
    $yamlform_confidential = YamlForm::load('test_form_confidential');
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('yamlform/test_form_confidential');
    $this->assertNoFieldById('edit-name');
    $this->assertRaw('This form is confidential.');

    // Check anonymous access to form.
    $this->drupalLogout();
    $this->drupalGet('yamlform/test_form_confidential');
    $this->assertFieldById('edit-name');
    $this->assertNoRaw('This form is confidential.');

    // Check that submission does not track the requests IP address.
    $sid = $this->postSubmission($yamlform_confidential, ['name' => 'John']);
    $yamlform_submission = YamlFormSubmission::load($sid);
    $this->assertEqual($yamlform_submission->getRemoteAddr(), t('(unknown)'));

    /* Test form preview (form_preview) */

    $this->drupalLogin($this->adminFormUser);

    $yamlform_preview = YamlForm::load('test_form_preview');

    // Check form with optional preview.
    $this->drupalGet('yamlform/test_form_preview');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', 'Preview');

    // Check default preview.
    $this->drupalPostForm('yamlform/test_form_preview', ['name' => 'test'], t('Preview'));

    $this->assertRaw('Please review your submission. Your submission is not complete until you press the "Submit" button!');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '< Previous');
    $this->assertRaw('<b>Name</b><br/>test');

    // Check required preview with custom settings.
    $yamlform_preview->setSettings([
      'preview' => DRUPAL_REQUIRED,
      'preview_next_button_label' => '{Preview}',
      'preview_prev_button_label' => '{Back}',
      'preview_message' => '{Message}',
    ]);
    $yamlform_preview->save();

    // Check custom preview.
    $this->drupalPostForm('yamlform/test_form_preview', ['name' => 'test'], t('{Preview}'));
    $this->assertRaw('{Message}');
    $this->assertFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Back}');
    $this->assertRaw('<b>Name</b><br/>test');

    $this->drupalGet('yamlform/test_form_preview');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertFieldByName('op', '{Preview}');

    /* Test results disabled (results_disabled=true) */

    // Check results disabled.
    $yamlform_disabled = YamlForm::load('test_submission_disabled');
    $submission = $this->postSubmission($yamlform_disabled);
    $this->assertFalse($submission, 'Submission not saved to the database.');

    // Check error message for admins.
    $this->drupalGet('yamlform/test_submission_disabled');
    $this->assertRaw(t('This form is currently not saving any submitted data.'));
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw(t('Unable to display this form. Please contact the site administrator.'));

    // Check form disable for everyone else.
    $this->drupalLogout();
    $this->drupalGet('yamlform/test_submission_disabled');
    $this->assertNoRaw(t('This form is currently not saving any submitted data.'));
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw(t('Unable to display this form. Please contact the site administrator.'));

    // Enabled ignore disabled results.
    $yamlform_disabled->setSetting('results_disabled_ignore', TRUE);
    $yamlform_disabled->save();
    $this->drupalLogin($this->adminFormUser);

    // Check no error message for admins.
    $this->drupalGet('yamlform/test_submission_disabled');
    $this->assertNoRaw(t('This form is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this form. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    // Check form not disabled and not messages for everyone else.
    $this->drupalLogout();
    $this->drupalGet('yamlform/test_submission_disabled');
    $this->assertNoRaw(t('This form is currently not saving any submitted data.'));
    $this->assertNoRaw(t('Unable to display this form. Please contact the site administrator.'));
    $this->assertFieldByName('op', 'Submit');

    /* Test token update (form_token_update) */

    // Post test submission.
    $this->drupalLogin($this->adminFormUser);
    $yamlform_token_update = YamlForm::load('test_token_update');
    $sid = $this->postSubmissionTest($yamlform_token_update);
    $yamlform_submission = YamlFormSubmission::load($sid);

    // Check token update access allowed.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($yamlform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertRaw('Submission information');
    $this->assertFieldByName('textfield', $yamlform_submission->getData('textfield'));

    // Check token update access denied.
    $yamlform_token_update->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($this->normalUser);
    $this->drupalGet($yamlform_submission->getTokenUrl());
    $this->assertResponse(200);
    $this->assertNoRaw('Submission information');
    $this->assertNoFieldByName('textfield', $yamlform_submission->getData('textfield'));

    /* Test limits (test_submission_limit) */

    $yamlform_limit = YamlForm::load('test_submission_limit');

    // Check form available.
    $this->drupalGet('yamlform/test_submission_limit');
    $this->assertFieldByName('op', 'Submit');

    $this->drupalLogin($this->normalUser);

    // Check that draft does not count toward limit.
    $this->postSubmission($yamlform_limit, [], t('Save Draft'));
    $this->drupalGet('yamlform/test_submission_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('A partially-completed form was found. Please complete the remaining portions.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this form.');

    // Check limit reached and form not available for authenticated user.
    $this->postSubmission($yamlform_limit);
    $this->drupalGet('yamlform/test_submission_limit');
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('You are only allowed to have 1 submission for this form.');

    $this->drupalLogout();

    // Check admin can still edit their submission.
    $this->drupalLogin($this->adminFormUser);
    $sid = $this->postSubmission($yamlform_limit);
    $this->drupalGet("admin/structure/yamlform/manage/test_submission_limit/submission/$sid/edit");
    $this->assertFieldByName('op', 'Save');
    $this->assertNoRaw('No more submissions are permitted.');
    $this->drupalLogout();

    // Check form is still available for anonymous users.
    $this->drupalGet('yamlform/test_submission_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertNoRaw('You are only allowed to have 1 submission for this form.');

    // Add 1 more submissions making the total number of submissions equal to 3.
    $this->postSubmission($yamlform_limit);

    // Check total limit.
    $this->assertNoFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertNoRaw('You are only allowed to have 1 submission for this form.');

    // Check admin can still post submissions.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('yamlform/test_submission_limit');
    $this->assertFieldByName('op', 'Submit');
    $this->assertRaw('Only 3 submissions are allowed.');
    $this->assertRaw('Only submission administrators are allowed to access this form and create new submissions.');
  }

}
