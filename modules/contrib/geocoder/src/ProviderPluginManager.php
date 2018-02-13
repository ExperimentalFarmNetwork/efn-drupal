<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\Annotation\GeocoderProvider;

/**
 * Provides a plugin manager for geocoder providers.
 */
class ProviderPluginManager extends GeocoderPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Geocoder/Provider', $namespaces, $module_handler, ProviderInterface::class, GeocoderProvider::class);
    $this->alterInfo('geocoder_provider_info');
    $this->setCacheBackend($cache_backend, 'geocoder_provider_plugins');
  }

}
