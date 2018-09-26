<?php

namespace Drupal\geofield\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Field handler to sort Geofields by proximity.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("geofield_proximity")
 */
class GeofieldProximity extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Data sources and info needed.
    $options['source'] = ['default' => 'manual'];

    $proximity_handlers = geofield_proximity_views_handlers();
    foreach ($proximity_handlers as $key => $handler) {
      $proximity_plugin = geofield_proximity_load_plugin($key);
      $proximity_plugin->option_definition($options, $this);
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $lat_alias = $this->tableAlias . '.' . $this->definition['field_name'] . '_lat';
    $lon_alias = $this->tableAlias . '.' . $this->definition['field_name'] . '_lon';

    $proximity_plugin = geofield_proximity_load_plugin($this->options['source']);
    $options = $proximity_plugin->getSourceValue($this);

    if ($options != FALSE) {
      $haversine_options = [
        'origin_latitude' => $options['latitude'],
        'origin_longitude' => $options['longitude'],
        'destination_latitude' => $lat_alias,
        'destination_longitude' => $lon_alias,
        'earth_radius' => GEOFIELD_KILOMETERS,
      ];
      $this->query->add_orderby(NULL, geofield_haversine($haversine_options), $this->options['order'], $this->tableAlias . '_geofield_distance');
    }
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

    $proximity_handlers = geofield_proximity_views_handlers();
    foreach ($proximity_handlers as $key => $handler) {
      $form['source']['#options'][$key] = $handler['name'];
      $proximity_plugin = geofield_proximity_load_plugin($key);
      $proximity_plugin->options_form($form, $form_state, $this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $proximity_plugin = geofield_proximity_load_plugin($form_state['values']['options']['source']);
    $proximity_plugin->options_validate($form, $form_state, $this);
  }

}
