<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Dumper\Gpx.
 */

namespace Drupal\geocoder\Plugin\Geocoder\Dumper;

use Drupal\geocoder\DumperBase;

/**
 * Provides a GPX geocoder dumper plugin.
 *
 * @GeocoderDumper(
 *   id = "gpx",
 *   name = "GPX",
 *   handler = "\Geocoder\Dumper\Gpx"
 * )
 */
class Gpx extends DumperBase {}
