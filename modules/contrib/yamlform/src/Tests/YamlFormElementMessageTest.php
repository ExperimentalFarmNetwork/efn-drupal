<?php

namespace Drupal\yamlform\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\yamlform\Entity\YamlForm;

/**
 * Tests for message form element.
 *
 * @group YamlForm
 */
class YamlFormElementMessageTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'yamlform', 'yamlform_test'];

  /**
   * Tests building of custom elements.
   */
  public function testMessage() {
    $yamlform = YamlForm::load('test_element_message');

    $this->drupalGet('yamlform/test_element_message');

    // Check basic message.
    $this->assertRaw('<div data-drupal-selector="edit-message-info" class="yamlform-message js-yamlform-message js-form-wrapper form-wrapper" id="edit-message-info">');
    $this->assertRaw('<div role="contentinfo" aria-label="" class="messages messages--info">');
    $this->assertRaw('This is an <strong>info</strong> message.');

    // Check close message with slide effect.
    $this->assertRaw('<div data-drupal-selector="edit-message-close-slide" class="yamlform-message js-yamlform-message yamlform-message--close js-yamlform-message--close js-form-wrapper form-wrapper" data-message-close-effect="slide" id="edit-message-close-slide">');
    $this->assertRaw('<div role="contentinfo" aria-label="" class="messages messages--info">');
    $this->assertRaw('<a href="#close" aria-label="close" class="js-yamlform-message__link yamlform-message__link">×</a>This is message that can be <b>closed using slide effect</b>.');

    // Set user and state storage.
    $elements = [
      'message_close_storage_user' => $yamlform->getElementDecoded('message_close_storage_user'),
      'message_close_storage_state' => $yamlform->getElementDecoded('message_close_storage_state'),
    ];
    $yamlform->setElements($elements);
    $yamlform->save();

    // Check that close links are not enabled for 'user' or 'state' storage
    // for anonymous users.
    $this->drupalGet('yamlform/test_element_message');
    $this->assertRaw('href="#close"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertNoRaw('data-message-storage="state"');

    // Login to test closing message via 'user' and 'state' storage.
    $this->drupalLogin($this->drupalCreateUser());

    // Check that close links are enabled.
    $this->drupalGet('yamlform/test_element_message');
    $this->assertNoRaw('href="#close"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertRaw('data-message-storage="user"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertRaw('data-message-storage="state"');

    // Close message using 'user' storage.
    $this->drupalGet('yamlform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'user' storage message is removed.
    $this->drupalGet('yamlform/test_element_message');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertRaw('data-message-storage="state"');

    // Close message using 'state' storage.
    $this->drupalGet('yamlform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'state' and 'user' storage message is removed.
    $this->drupalGet('yamlform/test_element_message');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertNoRaw('data-message-storage="state"');
  }

}
