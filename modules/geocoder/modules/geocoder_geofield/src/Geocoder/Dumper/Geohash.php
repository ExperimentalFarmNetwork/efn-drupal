<?php

/**
 * @file
 */

namespace Drupal\geocoder_geofield\Geocoder\Dumper;

use Drupal\geocoder\DumperInterface;
use Drupal\geocoder\DumperPluginManager;
use Geocoder\Dumper\Dumper;
use Geocoder\Model\Address;
use Drupal\geofield\geophp\geoPHPInterface;

/**
 * @author Pol Dellaiera <pol.dellaiera@gmail.com>
 */
class Geohash extends Geometry implements Dumper {
  /**
   * @var \Geocoder\Dumper\Dumper
   */
  protected $dumper;

  /**
   * @var GeoPHPWrapper
   */
  protected $geophp;

  /**
   * @inheritdoc
   */
  public function dump(Address $address) {
    return parent::dump($address)->out('geohash');
  }

}
