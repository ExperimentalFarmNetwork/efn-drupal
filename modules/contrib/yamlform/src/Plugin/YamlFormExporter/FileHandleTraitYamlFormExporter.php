<?php

namespace Drupal\yamlform\Plugin\YamlFormExporter;

/**
 * Defines file handle exporter trait.
 */
trait FileHandleTraitYamlFormExporter {

  /**
   * A file handler resource.
   *
   * @var resource
   */
  protected $fileHandle;

  /**
   * {@inheritdoc}
   */
  public function createExport() {
    $this->fileHandle = fopen($this->getExportFilePath(), 'w');
  }

  /**
   * {@inheritdoc}
   */
  public function openExport() {
    $this->fileHandle = fopen($this->getExportFilePath(), 'a');
  }

  /**
   * {@inheritdoc}
   */
  public function closeExport() {
    fclose($this->fileHandle);
  }

}
