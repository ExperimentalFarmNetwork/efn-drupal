<?php

/**
 * @file
 * Contains \Drupal\geofield\Tests\GeofieldFormatterTest.
 */

namespace Drupal\geofield\Tests;

use Drupal\filter\Entity\FilterFormat;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the geofield formatters functionality.
 *
 * @group geofield
 */
class GeofieldFormatterTest extends EntityUnitTestBase {

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('geophp', 'geofield');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    entity_create('field_storage_config', array(
      'field_name' => 'geofield',
      'entity_type' => $this->entityType,
      'type' => 'geofield',
      'settings' => array(
        'backend' => 'geofield_backend_default',
      ),
    ))->save();
    entity_create('field_config', array(
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'field_name' => 'geofield',
      'label' => 'GeoField',
    ))->save();
  }

  /**
   * Tests geofield field default formatter.
   */
  public function testFormatters() {
    // Create the entity to be referenced.
    $entity = entity_create($this->entityType, array('name' => $this->randomMachineName()));
    $value = \Drupal::service('geofield.wkt_generator')->WktGenerateGeometry();
    $entity->geofield = array(
      'value' => $value,
    );
    $entity->save();

      // Verify the geofield field formatter's render array.
      $build = $entity->get('geofield')->view(array('type' => 'geofield_default'));
      drupal_render($build[0]);
      $this->assertEqual($build[0]['#markup'], $value);
  }

}
