<?php

namespace Drupal\yamlform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\yamlform\Entity\YamlForm;
use Drupal\yamlform\Entity\YamlFormSubmission;
use Drupal\yamlform\YamlFormInterface;
use Drupal\yamlform\YamlFormRequestInterface;
use Drupal\yamlform\YamlFormSubmissionExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for form submission export.
 */
class YamlFormResultsExportController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The MIME type guesser.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $mimeTypeGuesser;

  /**
   * The form submission exporter.
   *
   * @var \Drupal\yamlform\YamlFormSubmissionExporterInterface
   */
  protected $submissionExporter;

  /**
   * Form request handler.
   *
   * @var \Drupal\yamlform\YamlFormRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a new YamlFormResultsExportController object.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser
   *   The MIME type guesser instance to use.
   * @param \Drupal\yamlform\YamlFormSubmissionExporterInterface $yamlform_submission_exporter
   *   The form submission exported.
   * @param \Drupal\yamlform\YamlFormRequestInterface $request_handler
   *   The form request handler.
   */
  public function __construct(MimeTypeGuesserInterface $mime_type_guesser, YamlFormSubmissionExporterInterface $yamlform_submission_exporter, YamlFormRequestInterface $request_handler) {
    $this->mimeTypeGuesser = $mime_type_guesser;
    $this->submissionExporter = $yamlform_submission_exporter;
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('file.mime_type.guesser'),
      $container->get('yamlform_submission.exporter'),
      $container->get('yamlform.request')
    );
  }

  /**
   * Returns form submission as a CSV.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array|null|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   *   A response that renders or redirects to the CSV file.
   */
  public function index(Request $request) {
    list($yamlform, $source_entity) = $this->requestHandler->getYamlFormEntities();
    $this->submissionExporter->setYamlForm($yamlform);
    $this->submissionExporter->setSourceEntity($source_entity);

    $query = $request->query->all();
    unset($query['destination']);
    if (isset($query['filename'])) {
      $build = $this->formBuilder()->getForm('Drupal\yamlform\Form\YamlFormResultsExportForm');

      // Redirect to file export.
      $file_path = $this->submissionExporter->getFileTempDirectory() . '/' . $query['filename'];
      if (file_exists($file_path)) {
        $route_name = $this->requestHandler->getRouteName($yamlform, $source_entity, 'yamlform.results_export_file');
        $route_parameters = $this->requestHandler->getRouteParameters($yamlform, $source_entity) + ['filename' => $query['filename']];
        $file_url = Url::fromRoute($route_name, $route_parameters, ['absolute' => TRUE])->toString();
        drupal_set_message($this->t('Export creation complete. Your download should begin now. If it does not start, <a href=":href">download the file here</a>. This file may only be downloaded once.', [':href' => $file_url]));
        $build['#attached']['html_head'][] = [
          [
            '#tag' => 'meta',
            '#attributes' => [
              'http-equiv' => 'refresh',
              'content' => '0; url=' . $file_url,
            ],
          ],
          'yamlform_results_export_download_file_refresh',
        ];
      }

      return $build;
    }
    elseif ($query && empty($query['ajax_form'])) {
      if (!empty($query['excluded_columns']) && is_string($query['excluded_columns'])) {
        $excluded_columns = explode(',', $query['excluded_columns']);
        $query['excluded_columns'] = array_combine($excluded_columns, $excluded_columns);
      }

      $export_options = $query + $this->submissionExporter->getDefaultExportOptions();
      $this->submissionExporter->setExporter($export_options);
      if ($this->submissionExporter->isBatch()) {
        self::batchSet($yamlform, $source_entity, $export_options);
        $route_name = $this->requestHandler->getRouteName($yamlform, $source_entity, 'yamlform.results_export');
        $route_parameters = $this->requestHandler->getRouteParameters($yamlform, $source_entity);
        return batch_process(Url::fromRoute($route_name, $route_parameters));
      }
      else {
        $this->submissionExporter->generate();
        $file_path = $this->submissionExporter->getExportFilePath();
        return $this->downloadFile($file_path, $export_options['download']);
      }

    }
    else {
      return $this->formBuilder()->getForm('Drupal\yamlform\Form\YamlFormResultsExportForm', $yamlform);
    }
  }

  /**
   * Returns form submission results as CSV file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $filename
   *   CSV file name.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   A response that renders or redirects to the CSV file.
   */
  public function file(Request $request, $filename) {
    list($yamlform, $source_entity) = $this->requestHandler->getYamlFormEntities();
    $this->submissionExporter->setYamlForm($yamlform);
    $this->submissionExporter->setSourceEntity($source_entity);

    $file_path = $this->submissionExporter->getFileTempDirectory() . '/' . $filename;
    if (!file_exists($file_path)) {
      $route_name = $this->requestHandler->getRouteName($yamlform, $source_entity, 'yamlform.results_export');
      $route_parameters = $this->requestHandler->getRouteParameters($yamlform, $source_entity);
      $t_args = [
        ':href' => Url::fromRoute($route_name, $route_parameters)->toString(),
      ];
      $build = [
        '#markup' => $this->t('No export file ready for download. The file may have already been downloaded by your browser. Visit the <a href=":href">download export form</a> to create a new export.', $t_args),
      ];
      return $build;
    }
    else {
      return $this->downloadFile($file_path);
    }
  }

  /**
   * Download generated CSV file.
   *
   * @param string $file_path
   *   The paths the generate CSV file.
   * @param bool $download
   *   Download the generated CSV file. Default to TRUE.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object containing the CSV file.
   */
  public function downloadFile($file_path, $download = TRUE) {
    // Return the export file.
    $contents = file_get_contents($file_path);
    unlink($file_path);

    $content_type = $this->mimeTypeGuesser->guess($file_path);

    if ($download) {
      $headers = [
        'Content-Length' => strlen($contents),
        'Content-Type' => $content_type,
        'Content-Disposition' => 'attachment; filename="' . basename($file_path) . '"',
      ];
    }
    else {
      if ($content_type != 'text/html') {
        $content_type = 'text/plain';
      }
      $headers = [
        'Content-Length' => strlen($contents),
        'Content-Type' => $content_type . '; charset=utf-8',
      ];
    }

    return new Response($contents, 200, $headers);
  }

  /****************************************************************************/
  // Batch functions.
  // Using static method to prevent the service container from being serialized.
  // "Prevents exception 'AssertionError' with message 'The container was serialized.'."
  /****************************************************************************/

  /**
   * Batch API; Initialize batch operations.
   *
   * @param \Drupal\yamlform\YamlFormInterface|null $yamlform
   *   A form.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A form source entity.
   * @param array $export_options
   *   An array of export options.
   *
   * @see http://www.jeffgeerling.com/blogs/jeff-geerling/using-batch-api-build-huge-csv
   */
  public static function batchSet(YamlFormInterface $yamlform, EntityInterface $source_entity = NULL, array $export_options) {
    if (!empty($export_options['excluded_columns']) && is_string($export_options['excluded_columns'])) {
      $excluded_columns = explode(',', $export_options['excluded_columns']);
      $export_options['excluded_columns'] = array_combine($excluded_columns, $excluded_columns);
    }

    /** @var \Drupal\yamlform\YamlFormSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('yamlform_submission.exporter');
    $submission_exporter->setYamlForm($yamlform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    $parameters = [
      $yamlform,
      $source_entity,
      $export_options,
    ];
    $batch = [
      'title' => t('Exporting submissions'),
      'init_message' => t('Creating export file'),
      'error_message' => t('The export file could not be created because an error occurred.'),
      'operations' => [
        [['\Drupal\yamlform\Controller\YamlFormResultsExportController', 'batchProcess'], $parameters],
      ],
      'finished' => ['\Drupal\yamlform\Controller\YamlFormResultsExportController', 'batchFinish'],
    ];

    batch_set($batch);
  }

  /**
   * Batch API callback; Write the header and rows of the export to the export file.
   *
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   The form.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A form source entity.
   * @param array $export_options
   *   An associative array of export options.
   * @param mixed|array $context
   *   The batch current context.
   */
  public static function batchProcess(YamlFormInterface $yamlform, EntityInterface $source_entity = NULL, array $export_options, &$context) {
    /** @var \Drupal\yamlform\YamlFormSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('yamlform_submission.exporter');
    $submission_exporter->setYamlForm($yamlform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_sid'] = 0;
      $context['sandbox']['max'] = $submission_exporter->getQuery()->count()->execute();
      // Store entity ids and not the actual yamlform or source entity in the
      // $context to prevent "The container was serialized" errors.
      // @see https://www.drupal.org/node/2822023
      $context['results']['yamlform_id'] = $yamlform->id();
      $context['results']['source_entity_type'] = ($source_entity) ? $source_entity->getEntityTypeId() : NULL;
      $context['results']['source_entity_id'] = ($source_entity) ? $source_entity->id() : NULL;
      $context['results']['export_options'] = $export_options;
      $submission_exporter->writeHeader();
    }

    // Write CSV records.
    $query = $submission_exporter->getQuery();
    $query->condition('sid', $context['sandbox']['current_sid'], '>');
    $query->range(0, $submission_exporter->getBatchLimit());
    $entity_ids = $query->execute();
    $yamlform_submissions = YamlFormSubmission::loadMultiple($entity_ids);
    $submission_exporter->writeRecords($yamlform_submissions);

    // Track progress.
    $context['sandbox']['progress'] += count($yamlform_submissions);
    $context['sandbox']['current_sid'] = ($yamlform_submissions) ? end($yamlform_submissions)->id() : 0;

    $context['message'] = t('Exported @count of @total submissions...', ['@count' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    // Track finished.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Batch API callback; Completed export.
   *
   * @param bool $success
   *   TRUE if batch successfully completed.
   * @param array $results
   *   Batch results.
   * @param array $operations
   *   An array of function calls (not used in this function).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to download the exported results.
   */
  public static function batchFinish($success, array $results, array $operations) {
    $yamlform_id = $results['yamlform_id'];
    $entity_type = $results['source_entity_type'];
    $entity_id = $results['source_entity_id'];

    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = YamlForm::load($yamlform_id);
    /** @var \Drupal\Core\Entity\EntityInterface|null $source_entity */
    $source_entity = ($entity_type && $entity_id) ? \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id) : NULL;
    /** @var array $export_options */
    $export_options = $results['export_options'];

    /** @var \Drupal\yamlform\YamlFormSubmissionExporterInterface $submission_exporter */
    $submission_exporter = \Drupal::service('yamlform_submission.exporter');
    $submission_exporter->setYamlForm($yamlform);
    $submission_exporter->setSourceEntity($source_entity);
    $submission_exporter->setExporter($export_options);

    if (!$success) {
      $file_path = $submission_exporter->getExportFilePath();
      @unlink($file_path);
      $archive_path = $submission_exporter->getArchiveFilePath();
      @unlink($archive_path);
      drupal_set_message(t('Finished with an error.'));
    }
    else {
      $submission_exporter->writeFooter();

      $filename = $submission_exporter->getExportFileName();

      if ($submission_exporter->isArchive()) {
        $submission_exporter->writeExportToArchive();
        $filename = $submission_exporter->getArchiveFileName();
      }

      /** @var \Drupal\yamlform\YamlFormRequestInterface $request_handler */
      $request_handler = \Drupal::service('yamlform.request');
      $route_name = $request_handler->getRouteName($yamlform, $source_entity, 'yamlform.results_export');
      $route_parameters = $request_handler->getRouteParameters($yamlform, $source_entity);
      $redirect_url = Url::fromRoute($route_name, $route_parameters, ['query' => ['filename' => $filename], 'absolute' => TRUE]);
      return new RedirectResponse($redirect_url->toString());
    }
  }

}
