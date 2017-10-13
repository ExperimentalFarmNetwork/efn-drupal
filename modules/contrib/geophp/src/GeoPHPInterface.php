<?php


namespace Drupal\geophp;

interface GeoPHPInterface {
  /**
   * Retrieves the GeoPHP library current version.
   *
   * @return string
   *   The version value.
   */
  public function version();

  /**
   * Loads a geometry object given some parameters.
   *
   * @return geometry
   *   The geometry object
   */
  public function load();

  /**
   * @return mixed
   */
  public function getAdapterMap();

}
