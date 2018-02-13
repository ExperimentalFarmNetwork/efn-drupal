<?php

namespace Drupal\geofield\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\NumericField;
use Drupal\views\ResultRow;

/**
 * Field handler to render a Geofield proximity in Views.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("geofield_proximity")
 */
class GeofieldProximity extends NumericField {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Data sources and info needed.
    $options['source'] = ['default' => 'manual'];

    foreach (geofield_proximity_views_handlers() as $key => $handler) {
      $proximityPlugin = geofield_proximity_load_plugin($key);
      $proximityPlugin->option_definition($options, $this);
    }

    $options['radius_of_earth'] = ['default' => GEOFIELD_KILOMETERS];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['source'] = [
      '#type' => 'select',
      '#title' => t('Source of Origin Point'),
      '#description' => t('How do you want to enter your origin point?'),
      '#options' => [],
      '#default_value' => $this->options['source'],
    ];

    $proximityHandlers = geofield_proximity_views_handlers();
    foreach ($proximityHandlers as $key => $handler) {
      $form['source']['#options'][$key] = $handler['name'];
      $proximityPlugin = geofield_proximity_load_plugin($key);
      $proximityPlugin->options_form($form, $form_state, $this);
    }

    $form['radius_of_earth'] = [
      '#type' => 'select',
      '#title' => t('Unit of Measure'),
      '#description' => '',
      '#options' => geofield_radius_options(),
      '#default_value' => $this->options['radius_of_earth'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $proximityPlugin = geofield_proximity_load_plugin($form_state['values']['options']['source']);
    $proximityPlugin->options_validate($form, $form_state, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    if (isset($values->{$this->field_alias})) {
      return $values->{$this->field_alias};
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $lat_alias = $this->tableAlias . '.' . $this->definition['field_name'] . '_lat';
    $lon_alias = $this->tableAlias . '.' . $this->definition['field_name'] . '_lon';

    $proximityPlugin = geofield_proximity_load_plugin($this->options['source']);
    $options = $proximityPlugin->getSourceValue($this);

    if ($options != FALSE) {
      $haversine_options = [
        'origin_latitude' => $options['latitude'],
        'origin_longitude' => $options['longitude'],
        'destination_latitude' => $lat_alias,
        'destination_longitude' => $lon_alias,
        'earth_radius' => $this->options['radius_of_earth'],
      ];

      $this->field_alias = $this->query->add_field(NULL, geofield_haversine($haversine_options), $this->tableAlias . '_' . $this->field);
    }
  }

}
