<?php

namespace Drupal\yamlform\Plugin\YamlFormExporter;

use Drupal\yamlform\YamlFormExporterBase;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Defines abstract tabular exporter used to build CSV files and HTML tables.
 */
abstract class TabularBaseYamlFormExporter extends YamlFormExporterBase {

  use FileHandleTraitYamlFormExporter;

  /**
   * An associative array containing form elements keyed by name.
   *
   * @var array
   */
  protected $elements;

  /**
   * An associative array containing a form's field definitions.
   *
   * @var array
   */
  protected $fieldDefinitions;

  /****************************************************************************/
  // Header.
  /****************************************************************************/

  /**
   * Build export header using form submission field definitions and form element columns.
   *
   * @return array
   *   An array containing the export header.
   */
  protected function buildHeader() {
    $export_options = $this->getConfiguration();
    $this->fieldDefinitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    $header = [];
    foreach ($this->fieldDefinitions as $field_definition) {
      // Build a form element for each field definition so that we can
      // use YamlFormElement::buildExportHeader(array $element, $export_options).
      $element = [
        '#type' => ($field_definition['type'] == 'entity_reference') ? 'entity_autocomplete' : 'element',
        '#admin_title' => '',
        '#title' => (string) $field_definition['title'],
        '#yamlform_key' => (string) $field_definition['name'],
      ];
      $header = array_merge($header, $this->elementManager->invokeMethod('buildExportHeader', $element, $export_options));
    }

    // Build element columns headers.
    foreach ($elements as $element) {
      $header = array_merge($header, $this->elementManager->invokeMethod('buildExportHeader', $element, $export_options));
    }
    return $header;
  }

  /****************************************************************************/
  // Record.
  /****************************************************************************/

  /**
   * Build export record using a form submission.
   *
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   A form submission.
   *
   * @return array
   *   An array containing the export record.
   */
  protected function buildRecord(YamlFormSubmissionInterface $yamlform_submission) {
    $export_options = $this->getConfiguration();
    $this->fieldDefinitions = $this->getFieldDefinitions();
    $elements = $this->getElements();

    $record = [];

    // Build record field definition columns.
    foreach ($this->fieldDefinitions as $field_definition) {
      $this->formatRecordFieldDefinitionValue($record, $yamlform_submission, $field_definition);
    }

    // Build record element columns.
    $data = $yamlform_submission->getData();
    foreach ($elements as $column_name => $element) {
      $value = (isset($data[$column_name])) ? $data[$column_name] : '';
      $record = array_merge($record, $this->elementManager->invokeMethod('buildExportRecord', $element, $value, $export_options));
    }
    return $record;
  }

  /**
   * Get the field definition value from a form submission entity.
   *
   * @param array $record
   *   The record to be added to the export file.
   * @param \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission
   *   A form submission.
   * @param array $field_definition
   *   The field definition for the value.
   */
  protected function formatRecordFieldDefinitionValue(array &$record, YamlFormSubmissionInterface $yamlform_submission, array $field_definition) {
    $export_options = $this->getConfiguration();

    $field_name = $field_definition['name'];
    $field_type = $field_definition['type'];
    switch ($field_type) {
      case 'created':
      case 'changed':
        $record[] = date('Y-m-d H:i:s', $yamlform_submission->get($field_name)->value);
        break;

      case 'entity_reference':
        $element = [
          '#type' => 'entity_autocomplete',
          '#target_type' => $field_definition['target_type'],
        ];
        $value = $yamlform_submission->get($field_name)->target_id;
        $record = array_merge($record, $this->elementManager->invokeMethod('buildExportRecord', $element, $value, $export_options));
        break;

      case 'entity_url':
      case 'entity_title':
        if (empty($yamlform_submission->entity_type->value) || empty($yamlform_submission->entity_id->value)) {
          $record[] = '';
          break;
        }
        $entity_type = $yamlform_submission->entity_type->value;
        $entity_id = $yamlform_submission->entity_id->value;
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        if ($entity) {
          $record[] = ($field_type == 'entity_url') ? $entity->toUrl()->setOption('absolute', TRUE)->toString() : $entity->label();
        }
        else {
          $record[] = '';
        }
        break;

      default:
        $record[] = $yamlform_submission->get($field_name)->value;
        break;
    }
  }

  /****************************************************************************/
  // Form definitions and elements.
  /****************************************************************************/

  /**
   * Get a form's field definitions.
   *
   * @return array
   *   An associative array containing a form's field definitions.
   */
  protected function getFieldDefinitions() {
    if (isset($this->fieldDefinitions)) {
      return $this->fieldDefinitions;
    }

    $export_options = $this->getConfiguration();

    $this->fieldDefinitions = $this->entityStorage->getFieldDefinitions();
    $this->fieldDefinitions = array_diff_key($this->fieldDefinitions, $export_options['excluded_columns']);

    // Add custom entity reference field definitions which rely on the
    // entity type and entity id.
    if ($export_options['entity_reference_format'] == 'link' && isset($this->fieldDefinitions['entity_type']) && isset($this->fieldDefinitions['entity_id'])) {
      $this->fieldDefinitions['entity_title'] = [
        'name' => 'entity_title',
        'title' => t('Submitted to: Entity title'),
        'type' => 'entity_title',
      ];
      $this->fieldDefinitions['entity_url'] = [
        'name' => 'entity_url',
        'title' => t('Submitted to: Entity URL'),
        'type' => 'entity_url',
      ];
    }

    return $this->fieldDefinitions;
  }

  /**
   * Get form elements.
   *
   * @return array
   *   An associative array containing form elements keyed by name.
   */
  protected function getElements() {
    if (isset($this->elements)) {
      return $this->elements;
    }

    $export_options = $this->getConfiguration();

    $yamlform = $this->getYamlForm();
    $element_columns = $yamlform->getElementsFlattenedAndHasValue();
    $this->elements = array_diff_key($element_columns, $export_options['excluded_columns']);
    return $this->elements;
  }

}
