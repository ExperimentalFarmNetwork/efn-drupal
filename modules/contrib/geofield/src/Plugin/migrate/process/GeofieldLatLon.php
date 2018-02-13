<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\migrate\process\GeofieldLatLon.
 */
namespace Drupal\geofield\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process latitude and longitude and return the value for the D8 geofield.
 *
 * @MigrateProcessPlugin(
 *   id = "geofield_latlon"
 * )
 */
class GeofieldLatLon extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($lat, $lon) = $value;

    if (empty($lat) || empty($lon)) {
      return NULL;
     }

    $lonlat = \Drupal::service('geofield.wkt_generator')->WktBuildPoint(array($lon, $lat));

    return $lonlat;
  }

}
