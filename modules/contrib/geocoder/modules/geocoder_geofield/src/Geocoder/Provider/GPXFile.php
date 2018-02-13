<?php

namespace Drupal\geocoder_geofield\Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\Provider;

/**
 * Provides a file handler to be used by 'file' plugin.
 */
class GPXFile extends AbstractProvider implements Provider {

  /**
   * Geophp interface.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  private $geophp;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->geophp = \Drupal::service('geofield.geophp');
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'gpxfile';
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($filename) {
    $gpx_string = file_get_contents($filename);
    $geometry = $this->geophp->load($gpx_string, 'gpx');

    $results = [];
    foreach ($geometry->getComponents() as $component) {
      // Currently the Provider only supports GPX points, so skip the rest.
      if ('Point' !== $component->getGeomType()) {
        continue;
      }

      $resultSet = $this->getDefaults();
      $resultSet['latitude'] = $component->y();
      $resultSet['longitude'] = $component->x();

      $results[] = array_merge($this->getDefaults(), $resultSet);
    }

    if (!empty($results)) {
      return $this->returnResults($results);
    }

    throw new NoResult(sprintf('Could not find geo data in file: "%s".', basename($filename)));
  }

  /**
   * {@inheritdoc}
   */
  public function reverse($latitude, $longitude) {
    throw new UnsupportedOperation('The GPX plugin is not able to do reverse geocoding.');
  }

}
