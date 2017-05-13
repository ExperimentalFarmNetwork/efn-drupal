<?php

namespace Drupal\Tests\profile\Functional;

use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Tests various CRUD for Profile entity in browser.
 *
 * @group profile
 */
class ProfileTest extends ProfileTestBase {

  /**
   * Tests creating and editing a profile.
   */
  public function testCreateEditProfile() {
    $this->drupalLogin($this->adminUser);

    $profile_fullname = $this->randomString();
    $create_url = Url::fromRoute("entity.profile.type.{$this->type->id()}.user_profile_form", [
      'user' => $this->loggedInUser->id(),
      'profile_type' => $this->type->id(),
    ]);
    $this->drupalGet($create_url->toString());
    $this->assertSession()->titleEquals("Create {$this->type->label()} | Drupal");
    $edit = [
      'profile_fullname[0][value]' => $profile_fullname,
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\profile\ProfileStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $profile = $storage->loadDefaultByUser($this->loggedInUser, $this->type->id());
    $this->assertEquals($profile_fullname, $profile->get('profile_fullname')->value);

    $this->drupalGet($profile->toUrl('edit-form')->toString());
    $this->assertSession()->titleEquals("Edit {$this->type->label()} profile #{$profile->id()} | Drupal");

    $profile_fullname = $this->randomString();
    $edit = [
      'profile_fullname[0][value]' => $profile_fullname,
    ];
    $this->submitForm($edit, 'Save');

    $storage->resetCache([$profile->id()]);
    $profile = $storage->loadDefaultByUser($this->loggedInUser, $this->type->id());
    $this->assertEquals($profile_fullname, $profile->get('profile_fullname')->value);
  }

  /**
   * Tests that a profile belonging to an anonymous user can be edited.
   */
  public function testAnonymousProfileEdit() {
    $profile = $this->createProfile($this->type, User::getAnonymousUser());

    $this->drupalLogin($this->adminUser);
    $this->drupalGet($profile->toUrl('edit-form')->toString());
    $profile_fullname = $this->randomString();
    $edit = [
      'profile_fullname[0][value]' => $profile_fullname,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->addressEquals($profile->toUrl('collection')->toString());
  }

}
