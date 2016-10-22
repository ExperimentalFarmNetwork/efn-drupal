<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ProviderUsingHandlerWithAdapterBase;

/**
 * Provides a Yandex geocoder provider plugin.
 *
 * @GeocoderProvider(
 *   id = "yandex",
 *   name = "Yandex",
 *   handler = "\Geocoder\Provider\Yandex"
 * )
 */
class Yandex extends ProviderUsingHandlerWithAdapterBase {}
