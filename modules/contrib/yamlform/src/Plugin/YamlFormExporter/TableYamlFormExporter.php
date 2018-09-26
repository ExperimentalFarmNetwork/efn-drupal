<?php

namespace Drupal\yamlform\Plugin\YamlFormExporter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Defines a HTML table exporter.
 *
 * @YamlFormExporter(
 *   id = "table",
 *   label = @Translation("HTML Table"),
 *   description = @Translation("Exports results as an HTML table."),
 * )
 */
class TableYamlFormExporter extends TabularBaseYamlFormExporter {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'excel' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (isset($form['excel'])) {
      return $form;
    }

    $form['excel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open HTML table in Excel'),
      '#description' => $this->t('If checked, the download file extension will be change from .html to .xls.'),
      '#default_value' => $this->configuration['excel'],
      '#states' => [
        'visible' => [
          [':input.js-yamlform-exporter' => ['value' => 'table']],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {
    $header = $this->buildHeader();

    $file_handle = $this->fileHandle;

    if ($this->configuration['source_entity']) {
      $title = $this->configuration['source_entity']->label();
    }
    elseif ($this->configuration['yamlform']) {
      $title = $this->configuration['yamlform']->label();
    }
    else {
      $title = '';
    }

    $thead = [];
    foreach ($header as $item) {
      $thead[] = '<th>' . htmlentities($item) . '</th>';
    }

    fwrite($file_handle, '<!doctype html>');
    fwrite($file_handle, '<html>');
    fwrite($file_handle, '<head>');
    fwrite($file_handle, '<meta charset="utf-8">');
    if ($title) {
      fwrite($file_handle, '<title>' . $title . '</title>');
    }
    fwrite($file_handle, '</head>');
    fwrite($file_handle, '<body>');

    fwrite($file_handle, '<table border="1">');
    fwrite($file_handle, '<thead><tr bgcolor="#cccccc" valign="top">');
    fwrite($file_handle, implode("\n", $thead));
    fwrite($file_handle, '</tr></thead>');
    fwrite($file_handle, '<tbody>');
  }

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(YamlFormSubmissionInterface $yamlform_submission) {
    $record = $this->buildRecord($yamlform_submission);

    $file_handle = $this->fileHandle;

    $row = [];
    foreach ($record as $item) {
      $row[] = '<td>' . htmlentities($item) . '</td>';
    }

    fwrite($file_handle, '<tr valign="top">');
    fwrite($file_handle, implode("\n", $row));
    fwrite($file_handle, '</tr>');
  }

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {
    $file_handle = $this->fileHandle;

    fwrite($file_handle, '</tbody>');
    fwrite($file_handle, '</table>');
    fwrite($file_handle, '</body>');
    fwrite($file_handle, '</html>');
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return ($this->configuration['excel']) ? 'xls' : 'html';
  }

}
