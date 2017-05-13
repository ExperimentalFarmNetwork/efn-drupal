<?php

namespace Drupal\leaflet_views\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

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
class LeafletMarker extends RowPluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  /**
   * Does the row plugin support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * @var string The main entity type id for the view base table.
   */
  protected $entityTypeId;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // First base table should correspond to main entity type.
    $base_table = key($this->view->getBaseTables());
    $views_definition = \Drupal::service('views.views_data')->get($base_table);
    $this->entityTypeId = $views_definition['table']['entity type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Get a list of fields and a sublist of geo data fields in this view
    // @todo use $fields = $this->displayHandler->getFieldLabels();
    $fields = array();
    $fields_geo_data = array();
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $fields[$field_id] = $label;
      if (is_a($handler, 'Drupal\views\Plugin\views\field\Field')) {
        $field_storage_definitions = \Drupal::entityManager()
          ->getFieldStorageDefinitions($handler->getEntityType());
        $field_storage_definition = $field_storage_definitions[$handler->definition['field_name']];

        if ($field_storage_definition->getType() == 'geofield') {
          $fields_geo_data[$field_id] = $label;
        }
      }
    }

    // Check whether we have a geo data field we can work with
    if (!count($fields_geo_data)) {
      $form['error'] = array(
        '#markup' => $this->t('Please add at least one geofield to the view.'),
      );
      return;
    }

    // Map preset.
    $form['data_source'] = array(
      '#type' => 'select',
      '#title' => $this->t('Data Source'),
      '#description' => $this->t('Which field contains geodata?'),
      '#options' => $fields_geo_data,
      '#default_value' => $this->options['data_source'],
      '#required' => TRUE,
    );

    // Name field
    $form['name_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Title Field'),
      '#description' => $this->t('Choose the field which will appear as a title on tooltips.'),
      '#options' => $fields,
      '#default_value' => $this->options['name_field'],
      '#empty_value' => '',
    );

    $desc_options = $fields;
    // Add an option to render the entire entity using a view mode
    if ($this->entityTypeId) {
      $desc_options += array(
        '#rendered_entity' => '<' . $this->t('Rendered @entity entity', array('@entity' => $this->entityTypeId)) . '>',
      );
    }

    $form['description_field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Description Field'),
      '#description' => $this->t('Choose the field or rendering method which will appear as a description on tooltips or popups.'),
      '#options' => $desc_options,
      '#default_value' => $this->options['description_field'],
      '#empty_value' => '',
    );

    if ($this->entityTypeId) {

      // Get the human readable labels for the entity view modes.
      $view_mode_options = array();
      foreach (\Drupal::entityManager()
                 ->getViewModes($this->entityTypeId) as $key => $view_mode) {
        $view_mode_options[$key] = $view_mode['label'];
      }
      // The View Mode drop-down is visible conditional on "#rendered_entity"
      // being selected in the Description drop-down above.
      $form['view_mode'] = array(
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#description' => $this->t('View modes are ways of displaying entities.'),
        '#options' => $view_mode_options,
        '#default_value' => !empty($this->options['view_mode']) ? $this->options['view_mode'] : 'full',
        '#states' => array(
          'visible' => array(
            ':input[name="row_options[description_field]"]' => array(
              'value' => '#rendered_entity'
            )
          )
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $geofield_value = $this->view->getStyle()->getFieldValue($row->index, $this->options['data_source']);

    if (empty($geofield_value)) {
      return FALSE;
    }

    // @todo This assumes that the user has selected WKT as the geofield output
    // formatter in the views field settings, and fails otherwise. Very brittle.
    $result = leaflet_process_geofield($geofield_value);

    // Convert the list of geo data points into a list of leaflet markers.
    return $this->renderLeafletMarkers($result, $row);
  }

  /**
   * Converts the given list of geo data points into a list of leaflet markers.
   *
   * @param $points
   *   A list of geofield points from {@link leaflet_process_geofield()}.
   * @param ResultRow $row
   *   The views result row.
   * @return array
   *   List of leaflet markers.
   */
  protected function renderLeafletMarkers($points, ResultRow $row) {
    // Render the entity with the selected view mode
    $popup_body = '';
    if ($this->options['description_field'] === '#rendered_entity' && is_object($row->_entity)) {
      $entity = $row->_entity;
      $build = entity_view($entity, $this->options['view_mode']);
      $popup_body = drupal_render($build);
    }
    // Normal rendering via fields
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

      // Allow modules to adjust the marker
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
   * @param ResultRow $row
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
      $errors[] = $this->t('Row @row requires the data source to be configured.', array('@row' => $this->definition['title']));
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['data_source'] = array('default' => '');
    $options['name_field'] = array('default' => '');
    $options['description_field'] = array('default' => '');
    $options['view_mode'] = array('default' => 'teaser');

    return $options;
  }
}
