<?php

namespace Drupal\geocoder_geofield\Geocoder\Dumper;

use Geocoder\Dumper\Dumper;
use Geocoder\Model\Address;

/**
 * Dumper.
 */
class Geometry implements Dumper {

  /**
   * Dumper.
   *
   * @var \Geocoder\Dumper\Dumper
   */
  private $dumper;

  /**
   * Geophp interface.
   *
   * @var \Drupal\geofield\geophp\geoPHPInterface
   */
  private $geophp;

  /**
   * Address.
   *
   * @inheritdoc
   */
  public function dump(Address $address) {
    return $this->geophp->load($this->dumper->dump($address), 'json');
  }

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->dumper = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geojson');
    $this->geophp = \Drupal::service('geofield.geophp');
  }

}
