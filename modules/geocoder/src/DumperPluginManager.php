<?php

/**
 * @file
 * Contains \Drupal\geocoder\DumperPluginManager.
 */

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\Annotation\GeocoderDumper;

/**
 * Provides a plugin manager for geocoder dumpers.
 */
class DumperPluginManager extends GeocoderPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Geocoder/Dumper', $namespaces, $module_handler, DumperInterface::class, GeocoderDumper::class);
    $this->alterInfo('geocoder_dumper_info');
    $this->setCacheBackend($cache_backend, 'geocoder_dumper_plugins');
  }

}
