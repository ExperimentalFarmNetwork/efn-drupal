<?php

namespace Drupal\yamlform\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a results exporter annotation object.
 *
 * Plugin Namespace: Plugin\YamlFormExporter.
 *
 * For a working example, see
 * \Drupal\yamlform\Plugin\YamlFormExporter\DelimitedText/YamlFormExporter
 *
 * @see hook_yamlform_exporter_info_alter()
 * @see \Drupal\yamlform\YamlFormExporterInterface
 * @see \Drupal\yamlform\YamlFormExporterBase
 * @see \Drupal\yamlform\YamlFormExporterManager
 * @see plugin_api
 *
 * @Annotation
 */
class YamlFormExporter extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the results exporter.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The category in the admin UI where the block will be listed.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category = '';

  /**
   * A brief description of the results exporter.
   *
   * This will be shown when adding or configuring this results exporter.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Generates zipped archive.
   *
   * @var bool
   */
  public $archive = FALSE;

  /**
   * Using export options.
   *
   * @var bool
   */
  public $options = TRUE;

}
