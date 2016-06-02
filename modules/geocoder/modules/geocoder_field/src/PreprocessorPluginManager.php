<?php

/**
 * @file
 * Contains \Drupal\geocoder_field\PreprocessorPluginManager.
 */

namespace Drupal\geocoder_field;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\geocoder\GeocoderPluginManagerBase;
use Drupal\geocoder_field\Annotation\GeocoderPreprocessor;

/**
 * Provides a plugin manager for geocoder data preprocessors.
 */
class PreprocessorPluginManager extends GeocoderPluginManagerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Geocoder/Preprocessor', $namespaces, $module_handler, PreprocessorInterface::class, GeocoderPreprocessor::class);
    $this->alterInfo('geocoder_preprocessor_info');
    $this->setCacheBackend($cache_backend, 'geocoder_preprocessor_plugins');
  }

  /**
   * Pre-processes a field, running all plugins that support that field type.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field item list to be processsed.
   */
  public function preprocess(FieldItemListInterface &$field) {
    $type = $field->getFieldDefinition()->getType();

    // Get a list of plugins that are supporting fields of type $type.
    $definitions = array_filter($this->getDefinitions(),
      function($definition) use ($type) {
        return in_array($type, $definition['field_types']);
      }
    );

    // Sort definitions by 'weight'.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);

    foreach ($definitions as $plugin_id => $definition) {
      /** @var \Drupal\geocoder_field\PreprocessorInterface $preprocessor */
      $preprocessor = $this->createInstance($plugin_id);
      $preprocessor->setField($field)->preprocess();
    }
  }

}
