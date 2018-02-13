<?php

namespace Drupal\yamlform\Tests;

use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for form confirmation.
 *
 * @group YamlForm
 */
class YamlFormConfirmationTest extends YamlFormTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests form confirmation.
   */
  public function testConfirmation() {
    // Login the admin user.
    $this->drupalLogin($this->adminFormUser);

    /* Test confirmation message (confirmation_type=message) */

    // Check confirmation message.
    $this->drupalPostForm('yamlform/test_confirmation_message', [], t('Submit'));
    $this->assertRaw('This is a <b>custom</b> confirmation message.');
    $this->assertUrl('yamlform/test_confirmation_message');

    // Check confirmation page with custom query parameters.
    $this->drupalPostForm('yamlform/test_confirmation_message', [], t('Submit'), ['query' => ['custom' => 'param']]);
    $this->assertUrl('yamlform/test_confirmation_message', ['query' => ['custom' => 'param']]);

    /* Test confirmation inline (confirmation_type=inline) */

    $yamlform_confirmation_inline = YamlForm::load('test_confirmation_inline');

    // Check confirmation inline.
    $this->drupalPostForm('yamlform/test_confirmation_inline', [], t('Submit'));
    $this->assertRaw('<a href="' . $yamlform_confirmation_inline->toUrl()->toString() . '" rel="back" title="Back to form">Back to form</a>');
    $this->assertUrl('yamlform/test_confirmation_inline', ['query' => ['yamlform_id' => $yamlform_confirmation_inline->id()]]);

    // Check confirmation inline with custom query parameters.
    $this->drupalPostForm('yamlform/test_confirmation_inline', [], t('Submit'), ['query' => ['custom' => 'param']]);
    $this->assertRaw('<a href="' . $yamlform_confirmation_inline->toUrl()->toString() . '?custom=param" rel="back" title="Back to form">Back to form</a>');
    $this->assertUrl('yamlform/test_confirmation_inline', ['query' => ['custom' => 'param', 'yamlform_id' => $yamlform_confirmation_inline->id()]]);

    /* Test confirmation page (confirmation_type=page) */

    $yamlform_confirmation_page = YamlForm::load('test_confirmation_page');

    // Check confirmation page.
    $this->drupalPostForm('yamlform/test_confirmation_page', [], t('Submit'));
    $this->assertRaw('This is a custom confirmation page.');
    $this->assertRaw('<a href="' . $yamlform_confirmation_page->toUrl()->toString() . '" rel="back" title="Back to form">Back to form</a>');
    $this->assertUrl('yamlform/test_confirmation_page/confirmation');

    // Check that the confirmation page's 'Back to form 'link includes custom
    // query parameters.
    $this->drupalGet('yamlform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param']]);

    // Check confirmation page with custom query parameters.
    $this->drupalPostForm('yamlform/test_confirmation_page', [], t('Submit'), ['query' => ['custom' => 'param']]);
    $this->assertUrl('yamlform/test_confirmation_page/confirmation', ['query' => ['custom' => 'param']]);

    // TODO: (TESTING)  Figure out why the inline confirmation link is not including the query string parameters.
    // $this->assertRaw('<a href="' . $yamlform_confirmation_page->toUrl()->toString() . '?custom=param">Back to form</a>');

    /* Test confirmation page custom (confirmation_type=page) */

    $yamlform_confirmation_page_custom = YamlForm::load('test_confirmation_page_custom');

    // Check custom confirmation page.
    $this->drupalPostForm('yamlform/test_confirmation_page_custom', [], t('Submit'));
    $this->assertRaw('<div style="border: 10px solid red; padding: 1em;" class="yamlform-confirmation">');
    $this->assertRaw('<a href="' . $yamlform_confirmation_page_custom->toUrl()->toString() . '" rel="back" title="Custom back to link" class="button">Custom back to link</a>');

    // Check back link is hidden.
    $yamlform_confirmation_page_custom->setSetting('confirmation_back', FALSE);
    $yamlform_confirmation_page_custom->save();
    $this->drupalPostForm('yamlform/test_confirmation_page_custom', [], t('Submit'));
    $this->assertNoRaw('<a href="' . $yamlform_confirmation_page_custom->toUrl()->toString() . '" rel="back" title="Custom back to link" class="button">Custom back to link</a>');

    /* Test confirmation URL (confirmation_type=url) */

    // Check confirmation URL.
    $this->drupalPostForm('yamlform/test_confirmation_url', [], t('Submit'));
    $this->assertNoRaw('<h2 class="visually-hidden">Status message</h2>');
    $this->assertUrl('<front>');

    /* Test confirmation URL (confirmation_type=url_message) */

    // Check confirmation URL.
    $this->drupalPostForm('yamlform/test_confirmation_url_message', [], t('Submit'));
    $this->assertRaw('<h2 class="visually-hidden">Status message</h2>');
    $this->assertRaw('This is a custom confirmation message.');
    $this->assertUrl('<front>');
  }

}
