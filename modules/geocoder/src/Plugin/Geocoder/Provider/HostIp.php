<?php

/**
 * @file
 * Contains \Drupal\geocoder\Plugin\Geocoder\Provider\HostIp.
 */

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a HostIp geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "hostip",
 *   name = "HostIp",
 *   handler = "\Geocoder\Provider\HostIp"
 * )
 */
class HostIp extends ProviderUsingHandlerWithAdapterBase {}
