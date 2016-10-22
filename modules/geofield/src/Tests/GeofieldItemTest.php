<?php

/**
 * @file
 * Contains \Drupal\geofield\Tests\GeofieldItemTest.
 */

namespace Drupal\geofield\Tests;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\field\Tests\FieldUnitTestBase;

/**
 * Tests using entity fields of the geofield field type.
 *
 * @group geofield
 */
class GeofieldItemTest extends FieldUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('geophp', 'geofield');

  /**
   * Field storage entity.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig.
   */
  protected $fieldStorage;

  /**
   * Field entity.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;


  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Tests processed properties.
   */
  public function testCrudAndUpdate() {
    $entity_type = 'entity_test';
    $this->createField($entity_type);

    // Create an entity with a random geofield field.
    $entity = entity_create($entity_type);
    $entity->geofield_field->value = $value = \Drupal::service('geofield.wkt_generator')->WktGenerateGeometry();
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    $entity = entity_load($entity_type, $entity->id());
    $this->assertTrue($entity->geofield_field instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->geofield_field[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEqual($entity->geofield_field->value, $value);

    // Test computed values.
    $geom = \Drupal::service('geofield.geophp')->load($value);
    if (!empty($geom)) {
      $centroid = $geom->getCentroid();
      $bounding = $geom->getBBox();
      $computed = array();

      $computed['geo_type'] = $geom->geometryType();
      $computed['lon'] = $centroid->getX();
      $computed['lat'] = $centroid->getY();
      $computed['left'] = $bounding['minx'];
      $computed['top'] = $bounding['maxy'];
      $computed['right'] = $bounding['maxx'];
      $computed['bottom'] = $bounding['miny'];
      $computed['geohash'] = $geom->out('geohash');

      foreach ($computed as $index => $computed_value) {
        $this->assertEqual($entity->geofield_field->{$index}, $computed_value);
      }
    }

    // Test the generateSampleValue() method.
    $entity = entity_create($entity_type);
    $entity->geofield_field->generateSampleItems();
    $this->entityValidateAndSave($entity);
  }

  /**
   * Creates a geofield field storage and field.
   *
   * @param string $entity_type
   *   Entity type for which the field should be created.
   */
  protected function createField($entity_type) {
    // Create a field .
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => 'geofield_field',
      'entity_type' => $entity_type,
      'type' => 'geofield',
      'settings' => array(
        'backend' => 'geofield_backend_default',
      )
    ));
    $this->fieldStorage->save();
    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => $entity_type,
    ));
    $this->field->save();
  }

}
