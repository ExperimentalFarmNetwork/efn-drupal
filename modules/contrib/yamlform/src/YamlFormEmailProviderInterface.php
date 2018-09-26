<?php

namespace Drupal\yamlform;

/**
 * Defines the interface for form email provider.
 */
interface YamlFormEmailProviderInterface {

  /**
   * Get list of known contrib module that support HTML email.
   *
   * @return array
   *   An array containing known contrib module that support HTML email.
   */
  public function getModules();

  /**
   * Check if the YAML Form module should provide support for sending HTML emails.
   */
  public function check();

  /**
   * Check if form email handler is installed.
   */
  public function installed();

  /**
   * Install form's PHP mail handler which supports sending HTML emails.
   */
  public function install();

  /**
   * Uninstall form's PHP mail handler which supports sending HTML emails.
   */
  public function uninstall();

  /**
   * Get the HTML email provider module machine name.
   *
   * @return bool|string
   *   The HTML email provider module machine name.
   */
  public function getModule();

  /**
   * Get the HTML email provider human readable module name.
   *
   * @return bool|string
   *   The HTML email provider module name.
   */
  public function getModuleName();

  /**
   * Get the mail back-end plugin id.
   *
   * @return string
   *   The email handler plugin id.
   */
  public function getMailPluginId();

  /**
   * Get the mail back-end plugin definition.
   *
   * @return array
   *   A plugin definition array.
   */
  public function getMailPluginDefinition();

}
