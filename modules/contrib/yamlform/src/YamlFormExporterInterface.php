<?php

namespace Drupal\yamlform;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for results exporters.
 *
 * @see \Drupal\yamlform\Annotation\YamlFormExporter
 * @see \Drupal\yamlform\YamlFormExporterBase
 * @see \Drupal\yamlform\YamlFormExporterManager
 * @see \Drupal\yamlform\YamlFormExporterManagerInterface
 * @see plugin_api
 */
interface YamlFormExporterInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the results exporter label.
   *
   * @return string
   *   The results exporter label.
   */
  public function label();

  /**
   * Returns the results exporter description.
   *
   * @return string
   *   The results exporter description.
   */
  public function description();

  /**
   * Determine if exporter generates an archive.
   *
   * @return bool
   *   TRUE if exporter generates an archive.
   */
  public function isArchive();

  /**
   * Determine if exporter has options.
   *
   * @return bool
   *   TRUE if export has options.
   */
  public function hasOptions();

  /**
   * Returns the results exporter status.
   *
   * @return bool
   *   TRUE is the results exporter is available.
   */
  public function getStatus();

  /**
   * Create export.
   */
  public function createExport();

  /**
   * Open export.
   */
  public function openExport();

  /**
   * Close export.
   */
  public function closeExport();

  /**
   * Write header to export.
   */
  public function writeHeader();

  /**
   * Write submission to export.
   *
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   A form submission.
   */
  public function writeSubmission(YamlFormSubmissionInterface $yamlform_submission);

  /**
   * Write footer to export.
   */
  public function writeFooter();

  /**
   * Get export file temp directory.
   *
   * @return string
   *   The export file temp directory..
   */
  public function getFileTempDirectory();

  /**
   * Get form submission base file name.
   *
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   A form submission.
   *
   * @return string
   *   Form submission's base file name.
   */
  public function getSubmissionBaseName(YamlFormSubmissionInterface $yamlform_submission);

  /**
   * Get export file extension.
   *
   * @return string
   *   A file extension.
   */
  public function getFileExtension();

  /**
   * Get export base file name without an extension.
   *
   * @return string
   *   A base file name.
   */
  public function getBaseFileName();

  /**
   * Get export file name.
   *
   * @return string
   *   A file name.
   */
  public function getExportFileName();

  /**
   * Get export file path.
   *
   * @return string
   *   A file path.
   */
  public function getExportFilePath();

  /**
   * Get archive file name and path for a form.
   *
   * @return string
   *   Archive file name and path for a form
   */
  public function getArchiveFilePath();

  /**
   * Get archive file name for a form.
   *
   * @return string
   *   Archive file name.
   */
  public function getArchiveFileName();

}
