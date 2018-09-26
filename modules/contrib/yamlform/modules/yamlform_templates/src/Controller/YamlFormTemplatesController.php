<?php

namespace Drupal\yamlform_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\yamlform\Utility\YamlFormDialogHelper;
use Drupal\yamlform\YamlFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for form templates.
 */
class YamlFormTemplatesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Form storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $yamlformStorage;

  /**
   * Constructs a YamlFormTemplatesController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(AccountInterface $current_user, FormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
    $this->yamlformStorage = $entity_type_manager->getStorage('yamlform');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('form_builder'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns the form templates index page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array|RedirectResponse
   *   A render array representing the form templates index page or redirect
   *   response to a selected form via the filter's autocomplete.
   */
  public function index(Request $request) {
    $keys = $request->get('search');

    // Handler autocomplete redirect.
    if ($keys && preg_match('#\(([^)]+)\)$#', $keys, $match)) {
      if ($yamlform = $this->yamlformStorage->load($match[1])) {
        return new RedirectResponse($yamlform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $header = [
      $this->t('Title'),
      ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]],
      ['data' => $this->t('Operations'), 'colspan' => 2],
    ];

    $yamlforms = $this->getTemplates($keys);
    $rows = [];
    foreach ($yamlforms as $yamlform) {
      $route_parameters = ['yamlform' => $yamlform->id()];

      $row['title'] = $yamlform->toLink();
      $row['description']['data']['description']['#markup'] = $yamlform->get('description');
      if ($this->currentUser->hasPermission('create yamlform')) {
        $row['select']['data'] = [
          '#type' => 'operations',
          '#links' => [
            'duplicate' => [
              'title' => $this->t('Select'),
              'url' => Url::fromRoute('entity.yamlform.duplicate_form', $route_parameters),
              'attributes' => YamlFormDialogHelper::getModalDialogAttributes(640),
            ],
          ],
        ];
      }
      $row['preview']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'preview' => [
            'title' => $this->t('Preview'),
            'url' => Url::fromRoute('entity.yamlform.preview', $route_parameters),
            'attributes' => YamlFormDialogHelper::getModalDialogAttributes(800),
          ],
        ],
      ];
      $rows[] = $row;
    }

    $build = [];
    $build['filter_form'] = $this->formBuilder->getForm('\Drupal\yamlform_templates\Form\YamlFormTemplatesFilterForm', $keys);
    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('There are no templates available.'),
      '#cache' => [
        'contexts' => $this->yamlformStorage->getEntityType()->getListCacheContexts(),
        'tags' => $this->yamlformStorage->getEntityType()->getListCacheTags(),
      ],
    ];

    // Must preload libraries required by (modal) dialogs.
    $build['#attached']['library'][] = 'yamlform/yamlform.admin.dialog';

    return $build;
  }

  /**
   * Returns a form to add a new submission to a form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\yamlform\YamlFormInterface $yamlform
   *   The form this submission will be added to.
   *
   * @return array|NotFoundHttpException
   *   The form submission form.
   */
  public function previewForm(Request $request, YamlFormInterface $yamlform) {
    if (!$yamlform->isTemplate()) {
      return new NotFoundHttpException();
    }

    return $yamlform->getSubmissionForm([], 'preview');
  }

  /**
   * Get form templates.
   *
   * @param string $keys
   *   (optional) Filter templates by key word.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array form entity that are used as templates.
   */
  protected function getTemplates($keys = '') {
    $query = $this->yamlformStorage->getQuery();
    $query->condition('template', TRUE);
    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');
      $query->condition($or);
    }

    $query->sort('title');

    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      return [];
    }

    /* @var $entities \Drupal\yamlform\YamlFormInterface[] */
    $entities = $this->yamlformStorage->loadMultiple($entity_ids);

    // If the user is not a form admin, check view access to each form.
    if (!$this->isAdmin()) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('view')) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;

  }

  /**
   * Route preview title callback.
   *
   * @param \Drupal\yamlform\YamlFormInterface|null $yamlform
   *   A form.
   *
   * @return string
   *   The form label.
   */
  public function previewTitle(YamlFormInterface $yamlform = NULL) {
    return $this->t('Previewing @title template', ['@title' => $yamlform->label()]);
  }

  /**
   * Is the current user a form administrator.
   *
   * @return bool
   *   TRUE if the current user has 'administer yamlform' or 'edit any yamlform'
   *   permission.
   */
  protected function isAdmin() {
    return ($this->currentUser->hasPermission('administer yamlform') || $this->currentUser->hasPermission('edit any yamlform'));
  }

}
