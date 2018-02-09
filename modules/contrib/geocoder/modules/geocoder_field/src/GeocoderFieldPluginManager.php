<?php

namespace Drupal\geocoder_field;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\geocoder_field\Annotation\GeocoderField;

/**
 * The Geocoder Field Plugin manager.
 */
class GeocoderFieldPluginManager extends DefaultPluginManager {

  /**
   * The geocoder field preprocessor plugin manager service.
   *
   * @var \Drupal\geocoder_field\PreprocessorPluginManager
   */
  protected $preprocessorPluginManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new geocoder field plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\geocoder_field\PreprocessorPluginManager $preprocessor_plugin_manager
   *   The geocoder field preprocessor plugin manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    PreprocessorPluginManager $preprocessor_plugin_manager,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct('Plugin/Geocoder/Field', $namespaces, $module_handler, GeocoderFieldPluginInterface::class, GeocoderField::class);
    $this->alterInfo('geocode_field_info');
    $this->setCacheBackend($cache_backend, 'geocode_field_plugins');

    $this->preprocessorPluginManager = $preprocessor_plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Get Fields Options.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param array $field_types
   *   The field types array.
   *
   * @return mixed
   *   The options results.
   */
  private function getFieldsOptions($entity_type_id, $bundle, $field_name, array $field_types) {
    $options = [];

    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle) as $id => $definition) {
      if (in_array($definition->getType(), $field_types)
        && ($definition->getName()) !== $field_name
        && !in_array($id, ['title', 'revision_log'])) {
        $options[$id] = new TranslatableMarkup(
          '@label (@name) [@type]', [
            '@label' => $definition->getLabel(),
            '@name' => $definition->getName(),
            '@type' => $definition->getType(),
          ]);
      }
    }

    return $options;
  }

  /**
   * Returns the first plugin that handles a specific field type.
   *
   * @param string $field_type
   *   The type of field for which to find a plugin.
   *
   * @return \Drupal\geocoder_field\GeocoderFieldPluginInterface|null
   *   The plugin instance or NULL, if no plugin handles this field type.
   */
  public function getPluginByFieldType($field_type) {
    foreach ($this->getDefinitions() as $definition) {
      if (in_array($field_type, $definition['field_types'])) {
        /* @var \Drupal\geocoder_field\GeocoderFieldPluginInterface $geocoder_field_plugin */
        $geocoder_field_plugin = $this->createInstance($definition['id']);
        return $geocoder_field_plugin;
      }
    }

    return NULL;
  }

  /**
   * Gets a list of fields available as source for Geocode operations.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The array of source fields and their label.
   */
  public function getGeocodeSourceFields($entity_type_id, $bundle, $field_name) {
    return $this->getFieldsOptions(
      $entity_type_id,
      $bundle,
      $field_name,
      $this->preprocessorPluginManager->getGeocodeSourceFieldsTypes()
    );
  }

  /**
   * Gets a list of fields available as source for Reverse Geocode operations.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The array of source fields and their label.
   */
  public function getReverseGeocodeSourceFields($entity_type_id, $bundle, $field_name) {
    return $this->getFieldsOptions(
      $entity_type_id,
      $bundle,
      $field_name,
      $this->preprocessorPluginManager->getReverseGeocodeSourceFieldsTypes()
    );
  }

}
