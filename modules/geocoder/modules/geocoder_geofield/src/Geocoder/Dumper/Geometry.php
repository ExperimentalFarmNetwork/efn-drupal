<?php

/**
 * @file
 */

namespace Drupal\geocoder_geofield\Geocoder\Dumper;

use Drupal\geocoder\DumperPluginManager;
use Geocoder\Dumper\Dumper;
use Geocoder\Model\Address;
use Drupal\geophp\geoPHPInterface;

/**
 * @author Pol Dellaiera <pol.dellaiera@gmail.com>
 */
class Geometry implements Dumper {
  /**
   * @var \Geocoder\Dumper\Dumper
   */
  private $dumper;

  /**
   * @var \Drupal\geofield\geophp\geoPHPInterface
   */
  private $geophp;

  /**
   * @inheritdoc
   */
  public function dump(Address $address) {
    return $this->geophp->load($this->dumper->dump($address), 'json');
  }

  /**
   * @inheritDoc
   */
  public function __construct() {
    $this->dumper = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geojson');
    $this->geophp = \Drupal::service('geofield.geophp');
  }

}
