<?php

namespace Drupal\geocoder\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the Geocoder module.
 *
 * @group Geocoder
 */
class GeocoderTest extends WebTestBase {

  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['geocoder'];

  /**
   * {@inheritdoc}
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {

    parent::setUp();
    $this->user = $this->DrupalCreateUser([
      'administer site configuration',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function testMobileJsRedirectPageExists() {

    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigForm() {

    // Test form structure.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
    $config = $this->config('geocoder.settings');
    $this->assertFieldByName(
      'cache',
      $config->get('cache'),
      'Cache field has the default value'
    );

    $this->drupalPostForm(NULL, [
      'cache' => FALSE,
    ], t('Save configuration'));

    $this->drupalGet('admin/config/system/geocoder');
    $this->assertResponse(200);
    $this->assertFieldByName(
      'cache',
      TRUE,
      'Cahe field is OK.'
    );
  }

}
