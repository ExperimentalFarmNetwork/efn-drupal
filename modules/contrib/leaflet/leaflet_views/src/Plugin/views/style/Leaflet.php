<?php

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leaflet",
 *   title = @Translation("Leaflet"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class Leaflet extends StylePluginBase {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Choose a map preset
    $map_options = array();
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = $this->t($map['label']);
    }
    $form['map'] = array(
      '#title' => $this->t('Map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $this->options['map'] ?: '',
      '#required' => TRUE,
    );

    $form['height'] = array(
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => $this->t('px'),
      '#size' => 4,
      '#default_value' => $this->options['height'],
      '#required' => TRUE,
    );

    // @todo add note about adding leaflet attachments for data points.
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $height = $form_state->getValue(array('style_options', 'height'));
    if (!empty($style_options['height']) && (!is_numeric($height) || $height <= 0)) {
      $form_state->setError($form['height'], $this->t('Map height needs to be a positive number.'));
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
  public function query() {
    // Avoid querying the database; all feature data comes from attachments.
    $this->built = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $features = array();
    foreach ($this->view->attachment_before as $id => $attachment) {
      if (!empty($attachment['#leaflet-attachment'])) {
        $features = array_merge($features, $attachment['rows']);
        $this->view->element['#attached'] = NestedArray::mergeDeep($this->view->element['#attached'], $attachment['#attached']);
        unset($this->view->attachment_before[$id]);
      }
    }

    $map_info = leaflet_map_get_info($this->options['map']);
    // Enable layer control by default, if we have more than one feature group.
    if (self::hasFeatureGroups($features)) {
      $map_info['settings'] += array('layerControl' => TRUE);
    }
    $element = leaflet_render_map($map_info, $features, $this->options['height'] . 'px');

    // Merge #attached libraries.
    $this->view->element['#attached'] = NestedArray::mergeDeep($this->view->element['#attached'], $element['#attached']);
    $element['#attached'] =& $this->view->element['#attached'];

    return $element;
  }

  /**
   * Checks whether the given array of features contains any groups, i.e.
   * elements having the "group" key set to TRUE.
   *
   * @param array $features
   * @return bool
   */
  protected static function hasFeatureGroups(array $features) {
    foreach ($features as $feature) {
      if (!empty($feature['group'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (empty($this->options['map'])) {
      $errors[] = $this->t('Style @style requires a leaflet map to be configured.', array('@style' => $this->definition['title']));
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['map'] = array('default' => '');
    $options['height'] = array('default' => '400');
    return $options;
  }
}
