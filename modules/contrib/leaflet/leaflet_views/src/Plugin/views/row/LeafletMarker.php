<?php

namespace Drupal\leaflet_views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\ViewsData;
use Drupal\Leaflet\LeafletService;

/**
 * Plugin which formats a row as a leaflet marker.
 *
 * @ViewsRow(
 *   id = "leaflet_marker",
 *   title = @Translation("Leaflet Marker"),
 *   help = @Translation("Display the row as a leaflet marker."),
 *   display_types = {"leaflet"},
 * )
 */
class LeafletMarker extends RowPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   *
   * @var bool
   */
  protected $usesOptions = TRUE;

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * The main entity type id for the view base table.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Entity Field manager service property.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Display Repository service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The View Data service property.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * Constructs a LeafletMap style instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display
   *   The entity display manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Views\ViewsData $view_data
   *   The view data.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display,
    RendererInterface $renderer,
    ViewsData $view_data,
    LeafletService $leaflet_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplay = $entity_display;
    $this->renderer = $renderer;
    $this->viewsData = $view_data;
    $this->leafletService = $leaflet_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('views.views_data'),
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // First base table should correspond to main entity type.
    $base_table = key($this->view->getBaseTables());
    $views_definition = $this->viewsData->get($base_table);
    $this->entityTypeId = $views_definition['table']['entity type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get a list of fields and a sublist of geo data fields in this view.
    // @TODO use $fields = $this->displayHandler->getFieldLabels();
    $fields = [];
    $fields_geo_data = [];
    /* @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler */
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $fields[$field_id] = $label;
      if (is_a($handler, 'Drupal\views\Plugin\views\field\EntityField')) {
        /* @var \Drupal\views\Plugin\views\field\EntityField $handler */
        $field_storage_definitions = $this->entityFieldManager
          ->getFieldStorageDefinitions($handler->getEntityType());
        $field_storage_definition = $field_storage_definitions[$handler->definition['field_name']];

        if ($field_storage_definition->getType() == 'geofield') {
          $fields_geo_data[$field_id] = $label;
        }
      }
    }

    // Check whether we have a geo data field we can work with.
    if (!count($fields_geo_data)) {
      $form['error'] = [
        '#markup' => $this->t('Please add at least one geofield to the view.'),
      ];
      return;
    }

    // Map preset.
    $form['data_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Source'),
      '#description' => $this->t('Which field contains geodata?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source'],
      '#required' => TRUE,
    ];

    // Name field.
    $form['name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Title Field'),
      '#description' => $this->t('Choose the field which will appear as a title on tooltips.'),
      '#options' => $fields,
      '#default_value' => $this->options['name_field'],
      '#empty_value' => '',
    ];

    $desc_options = $fields;
    // Add an option to render the entire entity using a view mode.
    if ($this->entityTypeId) {
      $desc_options += [
        '#rendered_entity' => '<' . $this->t('Rendered @entity entity', ['@entity' => $this->entityTypeId]) . '>',
      ];
    }

    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description Field'),
      '#description' => $this->t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
      '#empty_value' => '',
    ];

    if ($this->entityTypeId) {

      // Get the human readable labels for the entity view modes.
      $view_mode_options = [];
      foreach ($this->entityDisplay->getViewModes($this->entityTypeId) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $form['view_mode'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View modes are ways of displaying entities.'),
        '#options' => $view_mode_options,
        '#default_value' => !empty($this->options['view_mode']) ? $this->options['view_mode'] : 'full',
        '#states' => [
          'visible' => [
            ':input[name="row_options[description_field]"]' => [
              'value' => '#rendered_entity',
            ],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    /* @var \Drupal\views\ResultRow $row */
    $geofield_value = $this->view->getStyle()->getFieldValue($row->index, $this->options['data_source']);

    if (empty($geofield_value)) {
      return FALSE;
    }

    // @TODO This assumes that the user has selected WKT as the geofield output
    // formatter in the views field settings, and fails otherwise. Very brittle.
    $result = $this->leafletService->leafletProcessGeofield($geofield_value);

    // Convert the list of geo data points into a list of leaflet markers.
    return $this->renderLeafletMarkers($result, $row);
  }

  /**
   * Converts the given list of geo data points into a list of leaflet markers.
   *
   * @param array $points
   *   A list of geofield points from
   *   {@link \Drupal::service('leaflet.service')->leafletProcessGeofield()}.
   * @param \Drupal\views\ResultRow $row
   *   The views result row.
   *
   * @return array
   *   List of leaflet markers.
   */
  protected function renderLeafletMarkers(array $points, ResultRow $row) {
    // Render the entity with the selected view mode.
    $popup_body = '';
    if ($this->options['description_field'] === '#rendered_entity' && is_object($row->_entity)) {
      $entity = $row->_entity;
      $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $this->options['view_mode'], $entity->language());
      $popup_body = $this->renderer->renderRoot($build);
    }
    // Normal rendering via fields.
    elseif ($this->options['description_field']) {
      $popup_body = $this->view->getStyle()
        ->getField($row->index, $this->options['description_field']);
    }

    $label = $this->view->getStyle()
      ->getField($row->index, $this->options['name_field']);

    foreach ($points as &$point) {
      $point['popup'] = $popup_body;
      $point['label'] = $label;

      // Allow sub-classes to adjust the marker.
      $this->alterLeafletMarker($point, $row);

      // Allow modules to adjust the marker.
      \Drupal::moduleHandler()
        ->alter('leaflet_views_feature', $point, $row, $this);
    }
    return $points;
  }

  /**
   * Chance for sub-classes to adjust the leaflet marker array.
   *
   * For example, this can be used to add in icon configuration.
   *
   * @param array $point
   *   The Marker Point.
   * @param \Drupal\views\ResultRow $row
   *   The Result rows.
   */
  protected function alterLeafletMarker(array &$point, ResultRow $row) {
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    // @todo raise validation error if we have no geofield.
    if (empty($this->options['data_source'])) {
      $errors[] = $this->t('Row @row requires the data source to be configured.', ['@row' => $this->definition['title']]);
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['data_source'] = ['default' => ''];
    $options['name_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['view_mode'] = ['default' => 'teaser'];

    return $options;
  }

}
