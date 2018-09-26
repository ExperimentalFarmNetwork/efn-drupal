<?php

namespace Drupal\Tests\geocoder_geofield\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the integration between geocoder with geofield.
 *
 * @group Geocoder
 */
class GeocoderGeofieldIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'geophp',
    'geofield',
    'field',
    'geocoder_geofield',
    'geocoder_geofield_test',
    'geocoder',
    'geocoder_field',
    'entity_test',
    'text',
    'user',
    'filter',
  ];

  /**
   * Tests the geocoding on Geofield field type.
   */
  public function testGeofield() {
    $this->installEntitySchema('entity_test');

    // The remote field.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'type' => 'text',
      'field_name' => 'foo',
    ])->save();
    $remote_field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'foo',
    ])->save();
    // The 'geofield' type field.
    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'type' => 'geofield',
      'field_name' => 'bar',
    ])->save();
    /** @var \Drupal\Core\Field\FieldConfigInterface $field */
    $field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'field_name' => 'bar',
      'third_party_settings' => [
        'geocoder_field' => [
          'method' => 'source',
          'field' => 'foo',
          'plugins' => ['test_provider'],
          'dumper' => 'wkt',
          'delta_handling' => 'default',
          'failure' => [
            'handling' => 'preserve',
            'status_message' => FALSE,
            'log' => FALSE,
          ],
        ],
      ],
    ]);
    $field->save();

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create(['name' => 'Baz', 'bundle' => 'entity_test']);
    $entity->foo->value = 'Gotham City';
    $entity->save();

    // Check that field 'bar' contains the geo-coded value.
    $this->assertSame('POINT(40.000000 20.000000)', $entity->bar->value);

    // Add an arbitrary value that 'test_provider' doesn't know to handle.
    $entity->foo->value = 'SOME MESS';
    $entity->save();

    // Check if value has been preserved on geocoding failure.
    $this->assertSame('POINT(40.000000 20.000000)', $entity->bar->value);

    // Change the failure handling policy to 'empty'.
    $field->setThirdPartySetting('geocoder_field', 'failure', [
      'handling' => 'empty',
      'status_message' => FALSE,
      'log' => FALSE,
    ])->save();
    // Re-load and re-save to geo-code again.
    $entity = EntityTest::load($entity->id());
    $entity->save();

    // Check that the target field has been emptied.
    $this->assertNull($entity->bar->value);
  }

}
