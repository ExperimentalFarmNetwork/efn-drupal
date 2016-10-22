<?php

namespace Drupal\Tests\profile\Functional;

/**
 * Tests profile field access functionality.
 *
 * @group profile
 */
class ProfileFieldAccessTest extends ProfileTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $otherUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access user profiles',
      'administer profile types',
      'administer profile fields',
      'administer profile display',
      'bypass profile access',
    ]);

    $user_permissions = [
      'access user profiles',
      "add own {$this->type->id()} profile",
      "edit own {$this->type->id()} profile",
      "view own {$this->type->id()} profile",
    ];

    $this->webUser   = $this->drupalCreateUser($user_permissions);
    $this->otherUser = $this->drupalCreateUser($user_permissions);
  }

  /**
   * Tests private profile field access.
   */
  public function testPrivateField() {
    $this->field->setThirdPartySetting('profile', 'profile_private', TRUE);
    $this->field->save();

    // Fill in a field value.
    $this->drupalLogin($this->webUser);
    $uid = $this->webUser->id();
    $secret = $this->randomMachineName();
    $this->drupalGet("user/$uid/{$this->type->id()}");
    $edit = [
      'profile_fullname[0][value]' => $secret,
    ];
    $this->submitForm($edit, t('Save'));

    // Verify that the private field value appears for the profile owner.
    $this->drupalGet($this->webUser->toUrl()->toString());
    $this->assertText($secret);

    // Verify that the private field value appears for the administrator.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->webUser->toUrl()->toString());
    $this->assertText($secret);

    // Verify that the private field value does not appear for other users.
    $this->drupalLogin($this->otherUser);
    $this->drupalGet($this->webUser->toUrl()->toString());
    $this->assertNoText($secret);
  }

}
