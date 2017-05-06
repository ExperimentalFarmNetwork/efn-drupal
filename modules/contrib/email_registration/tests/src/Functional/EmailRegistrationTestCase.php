<?php

namespace Drupal\Tests\email_registration\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the email registration module.
 *
 * @group email_registration
 */
class EmailRegistrationTestCase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('email_registration');

  /**
   * Test various behaviors for anonymous users.
   */
  public function testRegistration() {
    $user_config = $this->container->get('config.factory')->getEditable('user.settings');
    $email_registration_config = $this->container->get('config.factory')->getEditable('email_registration.settings');
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();
    // Try to register a user.
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = array(
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    );
    $this->drupalPostForm('/user/register', $register, t('Create new account'));
    $this->drupalLogout();

    $login = array(
      'name' => $name . '@example.com',
      'pass' => $pass,
    );
    $this->drupalPostForm('user/login', $login, t('Log in'));

    // Really basic confirmation that the user was created and logged in.
    $this->assertRaw('<title>' . $name . ' | Drupal</title>', t('User properly created, logged in.'));

    // Now try the immediate login.
    $this->drupalLogout();

    // Try to login with just username, should fail by default.
    $this->drupalGet('user/login');
    $this->assertText('Enter your e-mail address.');
    $this->assertText('E-mail');
    $this->assertNoText('E-mail or username');
    $login = array(
      'name' => $name,
      'pass' => $pass,
    );
    $this->drupalPostForm('user/login', $login, t('Log in'));
    $error_message = $this->xpath('//div[contains(@class, "error")]');
    $this->assertTrue(!empty($error_message), t('When login_with_username is false, a user cannot login with just their username.'));

    // Set login_with_username to TRUE and try to login with just username.
    $email_registration_config->set('login_with_username', TRUE)->save();
    $this->drupalGet('user/login');
    $this->assertText('Enter your e-mail address or username.');
    $this->assertText('E-mail or username');
    $this->drupalPostForm('user/login', $login, t('Log in'));
    $this->assertRaw('<title>' . $name . ' | Drupal</title>', t('When login_with_username is true, a user can login with just their username.'));
    $this->drupalLogout();

    $user_config
      ->set('verify_mail', FALSE)
      ->save();
    $name = $this->randomMachineName();
    $pass = $this->randomString(10);
    $register = array(
      'mail' => $name . '@example.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    );
    $this->drupalPostForm('/user/register', $register, t('Create new account'));
    $this->assertRaw('Registration successful. You are now logged in.', t('User properly created, immediately logged in.'));

    // Test email_registration_unique_username().
    $this->drupalLogout();
    $user_config
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();
    $name = $this->randomMachineName(32);
    $pass = $this->randomString(10);

    $this->createUser(array(), $name);
    $next_unique_name = email_registration_unique_username($name);

    $register = array(
      'mail' => $name . '@example2.com',
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    );
    $this->drupalPostForm('/user/register', $register, t('Create new account'));
    $account = user_load_by_mail($register['mail']);
    $this->assertTrue($next_unique_name === $account->getAccountName());
  }

}
