<?php

namespace Drupal\geocoder_geofield\Geocoder\Dumper;

use Geocoder\Dumper\Dumper;
use Geocoder\Model\Address;

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
