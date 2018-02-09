<?php

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Leaflet\LeafletService;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leafet_map",
 *   title = @Translation("Leaflet Map"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 *
 * @deprecated Should be removed in favor of other plugins.
 */
class LeafletMap extends StylePluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity type property.
   *
   * @var string
   */
  private $entityType;

  /**
   * The Entity Info service property.
   *
   * @var string
   */
  private $entityInfo;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

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
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

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
    LeafletService $leaflet_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplay = $entity_display;
    $this->renderer = $renderer;
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
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // For later use, set entity info related to the View's base table.
    $base_tables = array_keys($view->getBaseTables());
    $base_table = reset($base_tables);
    foreach ($this->entityManager->getDefinitions() as $key => $info) {
      if ($info->getDataTable() == $base_table) {
        $this->entityType = $key;
        $this->entityInfo = $info;
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    // Render map even if there is no data.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get a list of fields and a sublist of geo data fields in this view.
    $fields = [];
    $fields_geo_data = [];
    /* @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler */
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $fields[$field_id] = $label;
      if (is_a($handler, '\Drupal\views\Plugin\views\field\EntityField')) {
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
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Please add at least one Geofield to the View and come back here to set it as Data Source.'),
        '#attributes' => [
          'class' => ['leaflet-warning'],
        ],
        '#attached' => [
          'library' => [
            'leaflet/general',
          ],
        ],
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
      '#options' => array_merge(['' => ''], $fields),
      '#default_value' => $this->options['name_field'],
    ];

    $desc_options = array_merge(['' => ''], $fields);
    // Add an option to render the entire entity using a view mode.
    if ($this->entityType) {
      $desc_options += [
        '#rendered_entity' => $this->t('< @entity entity >', ['@entity' => $this->entityType]),
      ];
    }

    $form['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Description Field'),
      '#description' => $this->t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#required' => FALSE,
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
    ];

    if ($this->entityType) {

      // Get the human readable labels for the entity view modes.
      $view_mode_options = [];
      foreach ($this->entityDisplay->getViewModes($this->entityType) as $key => $view_mode) {
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
            ':input[name="style_options[description_field]"]' => [
              'value' => '#rendered_entity',
            ],
          ],
        ],
      ];
    }

    // Choose a map preset.
    $map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = $map['label'];
    }
    $form['map'] = [
      '#title' => $this->t('Map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $this->options['map'] ?: '',
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => $this->t('px'),
      '#size' => 4,
      '#default_value' => $this->options['height'],
      '#required' => TRUE,
    ];

    $form['icon'] = [
      '#title' => $this->t('Map Icon'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => !isset($this->options['icon']['iconUrl']),
    ];

    $form['icon']['iconUrl'] = [
      '#title' => $this->t('Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $this->options['icon']['iconUrl'] ?: '',
    ];

    $form['icon']['shadowUrl'] = [
      '#title' => $this->t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $this->options['icon']['shadowUrl'] ?: '',
    ];

    $form['icon']['iconSize'] = [
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('Size of the icon image in pixels.'),
    ];

    $form['icon']['iconSize']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['iconSize']['x']) ? $this->options['icon']['iconSize']['x'] : '',
    ];

    $form['icon']['iconSize']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['iconSize']['y']) ? $this->options['icon']['iconSize']['y'] : '',
    ];

    $form['icon']['iconAnchor'] = [
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.'),
    ];

    $form['icon']['iconAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['iconAnchor']['x']) ? $this->options['icon']['iconAnchor']['x'] : '',
    ];

    $form['icon']['iconAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['iconAnchor']['y']) ? $this->options['icon']['iconAnchor']['y'] : '',
    ];

    $form['icon']['shadowAnchor'] = [
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the shadow is shown.'),
    ];
    $form['icon']['shadowAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['shadowAnchor']['x']) ? $this->options['icon']['shadowAnchor']['x'] : '',
    ];
    $form['icon']['shadowAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['shadowAnchor']['y']) ? $this->options['icon']['shadowAnchor']['y'] : '',
    ];

    $form['icon']['popupAnchor'] = [
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the marker popup opens, relative to the anchor point.'),
    ];

    $form['icon']['popupAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['popupAnchor']['x']) ? $this->options['icon']['popupAnchor']['x'] : '',
    ];

    $form['icon']['popupAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($this->options['icon']['popupAnchor']['y']) ? $this->options['icon']['popupAnchor']['y'] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $style_options = $form_state->getValue('style_options');
    if (!empty($style_options['height']) && (!is_numeric($style_options['height']) || $style_options['height'] <= 0)) {
      $form_state->setError($form['height'], $this->t('Map height needs to be a positive number.'));
    }
    $icon_options = isset($style_options['icon']) ? $style_options['icon'] : [];
    if (!empty($icon_options['iconUrl']) && !UrlHelper::isValid($icon_options['iconUrl'])) {
      $form_state->setError($form['icon']['iconUrl'], $this->t('Icon URL is invalid.'));
    }
    if (!empty($icon_options['shadowUrl']) && !UrlHelper::isValid($icon_options['shadowUrl'])) {
      $form_state->setError($form['icon']['shadowUrl'], $this->t('Shadow URL is invalid.'));
    }
    if (!empty($icon_options['iconSize']['x']) && (!is_numeric($icon_options['iconSize']['x']) || $icon_options['iconSize']['x'] <= 0)) {
      $form_state->setError($form['icon']['iconSize']['x'], $this->t('Icon width needs to be a positive number.'));
    }
    if (!empty($icon_options['iconSize']['y']) && (!is_numeric($icon_options['iconSize']['y']) || $icon_options['iconSize']['y'] <= 0)) {
      $form_state->setError($form['icon']['iconSize']['y'], $this->t('Icon height needs to be a positive number.'));
    }
  }

  /**
   * Renders the View.
   */
  public function render() {
    $data = [];
    $geofield_name = $this->options['data_source'];
    if ($this->options['data_source']) {
      $this->renderFields($this->view->result);
      /* @var \Drupal\views\ResultRow $result */
      foreach ($this->view->result as $id => $result) {

        $geofield_value = $this->getFieldValue($id, $geofield_name);

        if (empty($geofield_value)) {
          // In case the result is not among the raw results, get it from the
          // rendered results.
          $geofield_value = $this->rendered_fields[$id][$geofield_name];
        }
        if (!empty($geofield_value)) {
          $points = $this->leafletService->leafletProcessGeofield($geofield_value);

          // Render the entity with the selected view mode.
          if ($this->options['description_field'] === '#rendered_entity' && isset($result->_entity)) {
            $entity = $result->_entity;
            $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $this->options['view_mode'], $entity->language());
            $description = $this->renderer->renderRoot($build);
          }
          // Normal rendering via fields.
          elseif ($this->options['description_field']) {
            $description = $this->rendered_fields[$id][$this->options['description_field']];
          }

          // Attach pop-ups if we have a description field.
          if (isset($description)) {
            foreach ($points as &$point) {
              $point['popup'] = $description;
            }
          }

          // Attach also titles, they might be used later on.
          if ($this->options['name_field']) {
            foreach ($points as &$point) {
              $point['label'] = $this->rendered_fields[$id][$this->options['name_field']];
            }
          }

          $data = array_merge($data, $points);

          if (!empty($this->options['icon']) && $this->options['icon']['iconUrl']) {
            foreach ($data as $key => $feature) {
              $data[$key]['icon'] = $this->options['icon'];
            }
          }
        }
      }
    }

    // Always render the map, even if we do not have any data.
    $map = leaflet_map_get_info($this->options['map']);
    return $this->leafletService->leafletRenderMap($map, $data, $this->options['height'] . 'px');
  }

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['data_source'] = ['default' => ''];
    $options['name_field'] = ['default' => ''];
    $options['description_field'] = ['default' => ''];
    $options['view_mode'] = ['default' => 'full'];
    $options['map'] = ['default' => ''];
    $options['height'] = ['default' => '400'];
    $options['icon'] = ['default' => []];
    return $options;
  }

}
