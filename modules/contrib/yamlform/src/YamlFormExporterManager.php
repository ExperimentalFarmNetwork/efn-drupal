<?php

namespace Drupal\yamlform;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages results exporter plugins.
 *
 * @see hook_yamlform_exporter_info_alter()
 * @see \Drupal\yamlform\Annotation\YamlFormExporter
 * @see \Drupal\yamlform\YamlFormExporterInterface
 * @see \Drupal\yamlform\YamlFormExporterBase
 * @see plugin_api
 */
class YamlFormExporterManager extends DefaultPluginManager implements YamlFormExporterManagerInterface {

  use CategorizingPluginManagerTrait {
    getSortedDefinitions as traitGetSortedDefinitions;
  }

  /**
   * Constructs a new YamlFormExporterManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_exporter
   *   The module exporter.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_exporter) {
    parent::__construct('Plugin/YamlFormExporter', $namespaces, $module_exporter, 'Drupal\yamlform\YamlFormExporterInterface', 'Drupal\yamlform\Annotation\YamlFormExporter');

    $this->alterInfo('yamlform_exporter_info');
    $this->setCacheBackend($cache_backend, 'yamlform_exporter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSortedDefinitions(array $definitions = NULL) {
    // Sort the plugins first by category, then by label.
    $definitions = $this->traitGetSortedDefinitions($definitions);
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstances(array $configuration = []) {
    $instances = [];
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $instances[$plugin_id] = $this->createInstance($plugin_id, $configuration);
    }
    return $instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $plugin_definitions = $this->getDefinitions();
    $plugin_definitions = $this->getSortedDefinitions($plugin_definitions);

    $options = [];
    foreach ($plugin_definitions as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'delimited';
  }

}
