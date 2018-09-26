<?php

namespace Drupal\yamlform\Plugin\YamlFormExporter;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\yamlform\Utility\YamlFormTidy;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Defines a YAML document exporter.
 *
 * @YamlFormExporter(
 *   id = "yaml",
 *   label = @Translation("YAML documents"),
 *   description = @Translation("Exports results as YAML documents."),
 *   archive = TRUE,
 *   options = FALSE,
 * )
 */
class YamlYamlFormExporter extends DocumentBaseYamlFormExporter {

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(YamlFormSubmissionInterface $yamlform_submission) {
    $file_name = $this->getSubmissionBaseName($yamlform_submission) . '.yml';
    $yaml = Yaml::encode($yamlform_submission->toArray(TRUE));
    $yaml = YamlFormTidy::tidy($yaml);

    $archiver = new ArchiveTar($this->getArchiveFilePath(), 'gz');
    $archiver->addString($file_name, $yaml);
  }

}
