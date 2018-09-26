<?php

namespace Drupal\yamlform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\yamlform\YamlFormInterface;
use Drupal\yamlform\YamlFormRequestInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route responses for form.
 */
class YamlFormController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Form request handler.
   *
   * @var \Drupal\yamlform\YamlFormRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a new YamlFormSubmissionController object.
   *
   * @param \Drupal\yamlform\YamlFormRequestInterface $request_handler
   *   The form request handler.
   */
  public function __construct(YamlFormRequestInterface $request_handler) {
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yamlform.request')
    );
  }

  /**
   * Returns a form to add a new submission to a form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   The form this submission will be added to.
   *
   * @return array
   *   The form submission form.
   */
  public function addForm(Request $request, YamlFormInterface $yamlform) {
    return $yamlform->getSubmissionForm();
  }

  /**
   * Returns a form's CSS.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   The form.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function css(Request $request, YamlFormInterface $yamlform) {
    return new Response($yamlform->getCss(), 200, ['Content-Type' => 'text/css']);
  }

  /**
   * Returns a form's JavaScript.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   The form.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   */
  public function javascript(Request $request, YamlFormInterface $yamlform) {
    return new Response($yamlform->getJavaScript(), 200, ['Content-Type' => 'text/javascript']);
  }

  /**
   * Returns a form confirmation page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\yamlform\YamlFormInterface|null $yamlform
   *   A form.
   *
   * @return array
   *   A render array representing a form confirmation page
   */
  public function confirmation(Request $request, YamlFormInterface $yamlform = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$yamlform) {
      list($yamlform, $source_entity) = $this->requestHandler->getYamlFormEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('yamlform');
    }

    /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
    $yamlform_submission = NULL;

    if ($token = $request->get('token')) {
      /** @var \Drupal\yamlform\YamlFormSubmissionStorageInterface $yamlform_submission_storage */
      $yamlform_submission_storage = $this->entityTypeManager()->getStorage('yamlform_submission');
      if ($entities = $yamlform_submission_storage->loadByProperties(['token' => $token])) {
        $yamlform_submission = reset($entities);
      }
    }

    return [
      '#title' => ($source_entity) ? $source_entity->label() : $yamlform->label(),
      '#theme' => 'yamlform_confirmation',
      '#yamlform' => $yamlform,
      '#source_entity' => $source_entity,
      '#yamlform_submission' => $yamlform_submission,
    ];
  }

  /**
   * Returns a form filter form autocomplete matches.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param bool $templates
   *   If TRUE, limit autocomplete matches to form templates.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function autocomplete(Request $request, $templates = FALSE) {
    $q = $request->query->get('q');

    $yamlform_storage = $this->entityTypeManager()->getStorage('yamlform');

    $query = $yamlform_storage->getQuery()
      ->condition('title', $q, 'CONTAINS')
      ->range(0, 10)
      ->sort('title');

    // Limit query to templates.
    if ($templates) {
      $query->condition('template', TRUE);
    }
    elseif ($this->moduleHandler()->moduleExists('yamlform_templates')) {
      // Filter out templates if the yamlform_template.module is enabled.
      $query->condition('template', FALSE);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      return new JsonResponse([]);
    }
    $yamlforms = $yamlform_storage->loadMultiple($entity_ids);

    $matches = [];
    foreach ($yamlforms as $yamlform) {
      if ($yamlform->access('view')) {
        $value = new FormattableMarkup('@label (@id)', ['@label' => $yamlform->label(), '@id' => $yamlform->id()]);
        $matches[] = ['value' => $value, 'label' => $value];
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\yamlform\YamlFormInterface|null $yamlform
   *   A form.
   *
   * @return string
   *   The form label as a render array.
   */
  public function title(YamlFormInterface $yamlform = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
    if (!$yamlform) {
      list($yamlform, $source_entity) = $this->requestHandler->getYamlFormEntities();
    }
    else {
      $source_entity = $this->requestHandler->getCurrentSourceEntity('yamlform');
    }
    return ($source_entity) ? $source_entity->label() : $yamlform->label();
  }

}
