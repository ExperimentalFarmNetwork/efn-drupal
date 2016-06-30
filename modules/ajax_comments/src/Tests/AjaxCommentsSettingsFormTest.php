<?php

namespace Drupal\ajax_comments\Tests;

use Drupal\comment\Tests\CommentTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests the \Drupal\ajax_comments\Form\SettingsForm.
 *
 * @group ajax_comments
 */
class AjaxCommentsSettingsFormTest extends CommentTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'block',
    'comment',
    'node',
    'ajax_comments',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin_roles = $this->adminUser->getRoles();
    $admin_role = Role::load(reset($admin_roles));
    $this->grantPermissions($admin_role, ['administer site configuration']);
  }

  /**
   * Test the \Drupal\ajax_comments\Form\SettingsForm.
   */
  public function testAjaxCommentsSettings() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/ajax_comments');
    // Check that the page loads.
    $this->assertResponse(200);
    $this->assertText(
      t("Enable Ajax Comments on the comment fields' display settings"),
      'The list of bundles appears on the form.'
    );
    $this->clickLink(t('Content: Article'));
    $this->assertUrl('/admin/structure/types/manage/article/display', [], 'There is a link to the entity view display form for articles.');
  }

}
