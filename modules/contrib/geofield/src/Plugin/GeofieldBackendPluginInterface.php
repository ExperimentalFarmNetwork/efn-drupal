<?php

namespace Drupal\geofield\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Geofield backends.
 *
 * Modules implementing this interface may want to extend GeofieldBackendBase
 * class, which provides default implementations of each method.
 *
 * @see \Drupal\geofield\Annotation\GeofieldBackend
 * @see \Drupal\geofield\Plugin\GeofieldBackendBase
 * @see \Drupal\geofield\Plugin\GeofieldBackendManager
 * @see plugin_api
 */
interface GeofieldBackendPluginInterface extends PluginInspectionInterface {

  /**
   * Provides the specific database schema for the specific backend.
   *
   * @return array
   *   The schema.
   */
  public function schema();

  /**
   * Saves the Geo value into the specific backend.
   *
   * @param string $geometry
   *   The Geometry to save.
   */
  public function save($geometry);

  /**
   * Loads the Geo value from the specific backend.
   *
   * @param string $geometry
   *   The geometry to load.
   *
   * @return \Geometry|null
   *   The geometry output.
   */
  public function load($geometry);

}
