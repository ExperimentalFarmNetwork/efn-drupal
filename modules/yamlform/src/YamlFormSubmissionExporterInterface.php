<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for exporting form submission results.
 */
interface YamlFormSubmissionExporterInterface {

  /**
   * Set the form whose submissions are being exported.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   A form.
   */
  public function setYamlForm(YamlFormInterface $yamlform = NULL);

  /**
   * Get the form whose submissions are being exported.
   *
   * @return \Drupal\yamlform\YamlFormInterface
   *   A form.
   */
  public function getYamlForm();

  /**
   * Set the form source entity whose submissions are being exported.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A form's source entity.
   */
  public function setSourceEntity(EntityInterface $entity = NULL);

  /**
   * Get the form source entity whose submissions are being exported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A form's source entity.
   */
  public function getSourceEntity();

  /**
   * Get export options for the current form and entity.
   *
   * @return array
   *   Export options.
   */
  public function getYamlFormOptions();

  /**
   * Set export options for the current form and entity.
   *
   * @param array $options
   *   Export options.
   */
  public function setYamlFormOptions(array $options = []);

  /**
   * Delete export options for the current form and entity.
   */
  public function deleteYamlFormOptions();

  /**
   * Set results exporter.
   *
   * @param array $export_options
   *   Associative array of exporter options.
   *
   * @return \Drupal\yamlform\YamlFormExporterInterface
   *   A results exporter.
   */
  public function setExporter(array $export_options = []);

  /**
   * Get the results exporter.
   *
   * @return \Drupal\yamlform\YamlFormExporterInterface
   *   A results exporter.
   */
  public function getExporter();

  /**
   * Get export options.
   *
   * @return array
   *   Export options.
   */
  public function getExportOptions();

  /**
   * Get default export options.
   *
   * @return array
   *   Default export options.
   */
  public function getDefaultExportOptions();

  /**
   * Build export options form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $export_options
   *   The default values.
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options = []);

  /**
   * Get the values from the form's user input or form state values.
   *
   * @paran array $input
   *   An associative array of user input or form state values.
   *
   * @return array
   *   An associative array of export options.
   */
  public function getValuesFromInput(array $input);

  /**
   * Execute results exporter and write export to a temp file.
   */
  public function generate();

  /**
   * Write form results header to export file.
   */
  public function writeHeader();

  /**
   * Write form results header to export file.
   *
   * @param \Drupal\yamlform\YamlFormSubmissionInterface[] $yamlform_submissions
   *   A form submission.
   */
  public function writeRecords(array $yamlform_submissions);

  /**
   * Write form results footer to export file.
   */
  public function writeFooter();

  /**
   * Write export file to Archive file.
   */
  public function writeExportToArchive();

  /**
   * Get form submission query for specified YAMl form and export options.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   A form submission entity query.
   */
  public function getQuery();

  /**
   * Total number of submissions to be exported.
   *
   * @return int
   *   The total number of submissions to be exported.
   */
  public function getTotal();

  /**
   * Get the number of submissions to be exported with each batch.
   *
   * @return int
   *   Number of submissions to be exported with each batch.
   */
  public function getBatchLimit();

  /**
   * Determine if form submissions must be exported using batch processing.
   *
   * @return bool
   *   TRUE if form submissions must be exported using batch processing.
   */
  public function requiresBatch();

  /**
   * Get export file temp directory path.
   *
   * @return string
   *   Temp directory path.
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
   * Get export file name and path.
   *
   * @return string
   *   Export file name and path.
   */
  public function getExportFilePath();

  /**
   * Get export file name .
   *
   * @return string
   *   Export file name.
   */
  public function getExportFileName();

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

  /**
   * Determine if an archive is being generated.
   *
   * @return bool
   *   TRUE if an archive is being generated.
   */
  public function isArchive();

  /**
   * Determine if export needs to use batch processing.
   *
   * @return bool
   *   TRUE if export needs to use batch processing.
   */
  public function isBatch();

}
