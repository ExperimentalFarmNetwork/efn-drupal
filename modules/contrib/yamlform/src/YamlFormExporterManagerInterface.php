<?php

namespace Drupal\yamlform;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\CategorizingPluginManagerInterface;

/**
 * Collects available results exporters.
 */
interface YamlFormExporterManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface {

  /**
   * Get all available form element plugin instances.
   *
   * @param array $configuration
   *   Export configuration (aka export options).
   *
   * @return \Drupal\yamlform\YamlFormExporterInterface[]
   *   An array of all available form exporter plugin instances.
   */
  public function getInstances(array $configuration = []);

  /**
   * Get exporter plugins as options.
   *
   * @return array
   *   An associative array of options keyed by plugin id.
   */
  public function getOptions();

}
