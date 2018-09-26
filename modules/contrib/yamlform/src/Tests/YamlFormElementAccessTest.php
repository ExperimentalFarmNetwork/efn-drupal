<?php

namespace Drupal\yamlform\Tests;

use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Entity\YamlFormSubmission;

/**
 * Tests for form element access.
 *
 * @group YamlForm
 */
class YamlFormElementAccessTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['yamlform', 'yamlform_ui', 'yamlform_test'];

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $yamlform = YamlForm::load('test_element_access');

    // Check user from USER:1 to admin submission user.
    $elements = $yamlform->get('elements');
    $elements = str_replace('      - 1', '      - ' . $this->adminSubmissionUser->id(), $elements);
    $elements = str_replace('USER:1', 'USER:' . $this->adminSubmissionUser->id(), $elements);
    $yamlform->set('elements', $elements);
    $yamlform->save();

    // Create a form submission.
    $this->drupalLogin($this->normalUser);
    $sid = $this->postSubmission($yamlform);
    $yamlform_submission = YamlFormSubmission::load($sid);

    /* Test #private element property */

    // Check element with #private property hidden for normal user.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('yamlform/test_element_access');
    $this->assertNoFieldByName('private', '');

    // Check element with #private property visible for admin user.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('yamlform/test_element_access');
    $this->assertFieldByName('private', '');

    // Check admins have 'administer yamlform element access' permission.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet('admin/structure/yamlform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertFieldById('edit-properties-access-create-roles-anonymous');

    // Check form builder don't have 'administer yamlform element access'
    // permission.
    $this->drupalLogin($this->ownFormUser);
    $this->drupalGet('admin/structure/yamlform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $this->assertNoFieldById('edit-properties-access-create-roles-anonymous');

    /* Create access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet('yamlform/test_element_access');
    $this->assertFieldByName('access_create_roles_anonymous');
    $this->assertNoFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check authenticated access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet('yamlform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertNoFieldByName('access_create_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet('yamlform/test_element_access');
    $this->assertNoFieldByName('access_create_roles_anonymous');
    $this->assertFieldByName('access_create_roles_authenticated');
    $this->assertFieldByName('access_create_users');

    /* Create update */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet($yamlform_submission->getTokenUrl());
    $this->assertFieldByName('access_update_roles_anonymous');
    $this->assertNoFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check authenticated role access.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet("/yamlform/test_element_access/submissions/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertNoFieldByName('access_update_users');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet("/admin/structure/yamlform/manage/test_element_access/submission/$sid/edit");
    $this->assertNoFieldByName('access_update_roles_anonymous');
    $this->assertFieldByName('access_update_roles_authenticated');
    $this->assertFieldByName('access_update_users');

    /* Create view */

    // NOTE: Anonymous users can view submissions, so there is nothing to check.

    // Check authenticated role access.
    $this->drupalLogin($this->adminFormUser);
    $this->drupalGet("/admin/structure/yamlform/manage/test_element_access/submission/$sid");
    $this->assertNoRaw('access_view_roles (anonymous)');
    $this->assertRaw('access_view_roles (authenticated)');
    $this->assertNoRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

    // Check admin user access.
    $this->drupalLogin($this->adminSubmissionUser);
    $this->drupalGet("/admin/structure/yamlform/manage/test_element_access/submission/$sid");
    $this->assertNoRaw('access_view_roles (anonymous)');
    $this->assertRaw('access_view_roles (authenticated)');
    $this->assertRaw('access_view_users (USER:' . $this->adminSubmissionUser->id() . ')');

  }

}
