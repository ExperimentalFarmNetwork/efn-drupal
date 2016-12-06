<?php

namespace Drupal\yamlform_to_webform;

/**
 * Provides an interface for YAML Form to Webform migration.
 */
interface YamlFormToWebformMigrateManagerInterface {

  /**
   * Check requirements.
   *
   * @return array
   *   An associative array containing error messages.
   */
  public function requirements();

  /**
   * Migrate the YAML Form module's configuration and data to the Webform module.
   *
   * @return array
   *   An associative array containing status messages.
   */
  public function migrate();

}
