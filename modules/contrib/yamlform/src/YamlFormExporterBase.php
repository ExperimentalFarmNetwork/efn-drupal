<?php

namespace Drupal\yamlform;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a results exporter.
 *
 * @see \Drupal\yamlform\YamlFormExporterInterface
 * @see \Drupal\yamlform\YamlFormExporterManager
 * @see \Drupal\yamlform\YamlFormExporterManagerInterface
 * @see plugin_api
 */
abstract class YamlFormExporterBase extends PluginBase implements YamlFormExporterInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Form submission storage.
   *
   * @var \Drupal\yamlform\YamlFormSubmissionStorageInterface
   */
  protected $entityStorage;

  /**
   * The form element manager.
   *
   * @var \Drupal\yamlform\YamlFormElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\yamlform\YamlFormElementManagerInterface $element_manager
   *   The form element manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, EntityTypeManagerInterface $entity_type_manager, YamlFormElementManagerInterface $element_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityStorage = $entity_type_manager->getStorage('yamlform_submission');
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('yamlform'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.yamlform.element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isArchive() {
    return $this->pluginDefinition['archive'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasOptions() {
    return $this->pluginDefinition['options'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'yamlform' => NULL,
      'source_entity' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * Get the form whose submissions are being exported.
   *
   * @return \Drupal\yamlform\YamlFormInterface
   *   A form.
   */
  protected function getYamlForm() {
    return $this->configuration['yamlform'];
  }

  /**
   * Get the form source entity whose submissions are being exported.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A form's source entity.
   */
  protected function getSourceEntity() {
    return $this->configuration['source_entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function createExport() {}

  /**
   * {@inheritdoc}
   */
  public function openExport() {}

  /**
   * {@inheritdoc}
   */
  public function closeExport() {}

  /**
   * {@inheritdoc}
   */
  public function writeHeader() {}

  /**
   * {@inheritdoc}
   */
  public function writeSubmission(YamlFormSubmissionInterface $yamlform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function writeFooter() {}

  /**
   * {@inheritdoc}
   */
  public function getFileTempDirectory() {
    return file_directory_temp();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionBaseName(YamlFormSubmissionInterface $yamlform_submission) {
    $export_options = $this->getConfiguration();
    $file_name = $export_options['file_name'];
    $token_data = [
      'yamlform' => $yamlform_submission->getYamlForm(),
      'yamlform_submission' => $yamlform_submission,
    ];
    $token_options = ['clear' => TRUE];
    $file_name = \Drupal::token()->replace($file_name, $token_data, $token_options);

    // Sanitize file name.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    $file_name  = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $file_name);
    $file_name = preg_replace('/\s+/', '-', $file_name);
    return $file_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileExtension() {
    return 'txt';
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFileName() {
    $yamlform = $this->getYamlForm();
    $source_entity = $this->getSourceEntity();
    if ($source_entity) {
      return $yamlform->id() . '.' . $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    }
    else {
      return $yamlform->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFileName() {
    return $this->getBaseFileName() . '.' . $this->getFileExtension();
  }

  /**
   * {@inheritdoc}
   */
  public function getExportFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getExportFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFilePath() {
    return $this->getFileTempDirectory() . '/' . $this->getArchiveFileName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArchiveFileName() {
    return $this->getBaseFileName() . '.tar.gz';
  }

}
