<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerBase;

/**
 * Provides a File geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "file",
 *   name = "File",
 *   handler = "\Drupal\geocoder\Geocoder\Provider\File"
 * )
 */
class File extends ProviderUsingHandlerBase {}
