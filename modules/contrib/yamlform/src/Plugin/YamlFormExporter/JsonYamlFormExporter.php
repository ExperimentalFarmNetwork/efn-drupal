<?php

namespace Drupal\yamlform\Plugin\YamlFormExporter;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Defines a JSON document exporter.
 *
 * @YamlFormExporter(
 *   id = "json",
 *   label = @Translation("JSON documents"),
 *   description = @Translation("Exports results as JSON documents."),
 *   archive = TRUE,
 *   options = FALSE,
 * )
 */
class JsonYamlFormExporter extends DocumentBaseYamlFormExporter {

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(YamlFormSubmissionInterface $yamlform_submission) {
    $file_name = $this->getSubmissionBaseName($yamlform_submission) . '.json';
    $json = Json::encode($yamlform_submission->toArray(TRUE));

    $archiver = new ArchiveTar($this->getArchiveFilePath(), 'gz');
    $archiver->addString($file_name, $json);
  }

}
