<?php

namespace Drupal\field_states_ui;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages field state plugins.
 *
 * @see hook_field_state_info_alter()
 * @see \Drupal\field_states_ui\Annotation\FieldState
 * @see \Drupal\field_states_ui\FieldStateInterface
 * @see \Drupal\field_states_ui\FieldStateBase
 * @see plugin_api
 */
class FieldStateManager extends DefaultPluginManager {

  /**
   * Constructs a new FieldStateManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/FieldState', $namespaces, $module_handler, 'Drupal\field_states_ui\FieldStateInterface', 'Drupal\field_states_ui\Annotation\FieldState');

    $this->alterInfo('field_state_info');
    $this->setCacheBackend($cache_backend, 'field_state_plugins');
  }

}
