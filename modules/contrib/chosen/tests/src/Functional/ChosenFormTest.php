<?php

namespace Drupal\Tests\chosen\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Chosen form API test.
 *
 * @group chosen
 */
class ChosenFormTest extends BrowserTestBase {

  public static $modules = ['chosen', 'chosen_test'];

  /**
   * Test the form page.
   */
  public function testFormPage() {
    $this->drupalGet('chosen-test');
    $this->assertText('Select');
    $this->assertTrue($this->xpath('//select[@id=:id and contains(@class, :class)]', [':id' => 'edit-select', ':class' => 'chosen-enable']), 'The select has chosen enable class.');
  }

}
