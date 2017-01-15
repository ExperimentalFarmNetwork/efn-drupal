<?php

/**
 * @file
 * NG Lightbox tests.
 */

namespace Drupal\ng_lightbox\Tests;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\KernelTestBase;

/**
 * Test basic functionality of the lightbox.
 *
 * @group ng_lightbox
 */
class NgLightboxTest extends KernelTestBase {

  /**
   * @var array
   */
  public static $modules = ['system', 'node', 'user', 'ng_lightbox'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router', 'url_alias']);
    \Drupal::service('router.builder')->rebuild();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['ng_lightbox']);

    // Create the node type.
    NodeType::create(['type' => 'page'])->save();
  }

  /**
   * Test the pattern matching for link paths.
   */
  public function testPatternMatching() {

    // Test the patterns are enabled on links as expected.
    $node = Node::create(['type' => 'page', 'title' => $this->randomString()]);
    $node->save();
    $config = \Drupal::configFactory()->getEditable('ng_lightbox.settings');
    $config->set('patterns', $node->url())->save();
    $this->assertLightboxEnabled(\Drupal::l('Normal Path', $node->urlInfo()));

    // Create a second node and make sure it doesn't get lightboxed.
    $node = Node::create(['type' => 'page', 'title' => $this->randomString()]);
    $node->save();
    $this->assertLightboxNotEnabled(\Drupal::l('Normal Path', $node->urlInfo()));


    // @TODO, these were in D7 but in D8, I can't see how you can even generate
    // a link with such a format so maybe it isn't needed at all?
    // The uppercase path should still be matched for a lightbox.
    // $this->assertLightboxNotEnabled(\Drupal::l('Uppercase Path', 'NODE/1'));
    // $this->assertLightboxNotEnabled(\Drupal::l('Alaised Path', $alias));
    // $this->assertLightboxNotEnabled(\Drupal::l('Empty Path', ''));
  }

  /**
   * Asserts the lightbox was enabled for the generated link.
   *
   * @param string $link
   *   The rendered link.
   */
  protected function assertLightboxEnabled($link) {
    $this->assertContains('use-ajax', $link);
    $this->assertContains('data-dialog-type', $link);
  }

  /**
   * Asserts the lightbox was not enabled for the generated link.
   *
   * @param string $link
   *   The rendered link.
   */
  protected function assertLightboxNotEnabled($link) {
    $this->assertNotContains('use-ajax', $link);
    $this->assertNotContains('data-dialog-type', $link);
  }

  /**
   * Asserts a string does exist in the haystack.
   *
   * @param string $needle
   *   The string to search for.
   * @param string $haystack
   *   The string to search within.
   * @param string $message
   *   The message to log.
   *
   * @return bool
   *   TRUE if it was found otherwise FALSE.
   */
  protected function assertContains($needle, $haystack, $message = '') {
    if (empty($message)) {
      $message = t('%needle was found within %haystack', array('%needle' => $needle, '%haystack' => $haystack));
    }
    return $this->assertTrue(stripos($haystack, $needle) !== FALSE, $message);
  }

  /**
   * Asserts a string does not exist in the haystack.
   *
   * @param string $needle
   *   The string to search for.
   * @param string $haystack
   *   The string to search within.
   * @param string $message
   *   The message to log.
   *
   * @return bool
   *   TRUE if it was not found otherwise FALSE.
   */
  protected function assertNotContains($needle, $haystack, $message = '') {
    if (empty($message)) {
      $message = t('%needle was not found within %haystack', array('%needle' => $needle, '%haystack' => $haystack));
    }
    return $this->assertTrue(stripos($haystack, $needle) === FALSE, $message);
  }

}
