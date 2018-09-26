<?php

namespace Drupal\leaflet;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\ViewsPluginInterface;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common functions for Leaflet Settings Elements.
 *
 * @package Drupal\leaflet
 */
trait LeafletSettingsElementsTrait {

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $gMapTypesOptions = [
    'roadmap' => 'Roadmap',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
    'terrain' => 'Terrain',
  ];

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $infowindowFieldTypesOptions = [
    'string_long',
    'string',
    'text',
    'text_long',
    "text_with_summary",
  ];

  protected $customMapStylePlaceholder = '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0e1626"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#4e6d70"}]}]';

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface $this->link
   */

  /**
   * Generate the Leaflet Map General Settings.
   *
   * @param array $elements
   *   The form elements.
   * @param array $settings
   *   The settings.
   */
  protected function generateMapGeneralSettings(array &$elements, array $settings) {

    $leaflet_map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $leaflet_map_options[$key] = $map['label'];
    }

    $leaflet_map = isset($settings['leaflet_map']) ? $settings['leaflet_map'] : $settings['map'];

    $elements['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $leaflet_map_options,
      '#default_value' => $leaflet_map,
      '#required' => TRUE,
    ];

    $elements['height'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#default_value' => $settings['height'],
      '#field_suffix' => $this->t('px'),
    ];

    $elements['hide_empty_map'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Map if empty'),
      '#description' => $this->t('Check this option not to render the Map at all, if empty (no output results).'),
      '#default_value' => $settings['hide_empty_map'],
      '#return_value' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][multiple_map]"]' => ['checked' => TRUE],
        ],
      ],
    ];

  }

  /**
   * Generate the Leaflet Map Position Form Element.
   *
   * @param array $map_position_options
   *   The map position options array definition.
   *
   * @return array
   *   The Leaflet Map Position Form Element.
   */
  protected function generateMapPositionElement(array $map_position_options) {

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Starting Map State'),
    ];

    $force_checkbox_selector = ':input[name="fields[field_geofield][settings_edit_form][settings][map_position][force]"]';
    if ($this instanceof ViewsPluginInterface) {
      $force_checkbox_selector = ':input[name="style_options[map_position][force]"]';
    }

    $element['description'] = [
      '#type' => 'container',
      'html_tag' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('These settings will be applied in case of single Marker Map (otherwise the Zoom will be set to Fit Markers bounds).'),
      ],
      '#states' => [
        'invisible' => [
          $force_checkbox_selector => ['checked' => TRUE],
        ],
      ],
    ];

    $element['force'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('These settings will be forced anyway as starting Map state.'),
      '#default_value' => $map_position_options['force'],
      '#return_value' => 1,
    ];

    if ($this instanceof ViewsPluginInterface) {
      $element['#title'] = $this->t('Custom Map Center & Zoom');
      $element['description']['#value'] = $this->t('These settings will be applied in case of empty Map.');
      $element['force']['#title'] = $this->t('Force Map Center & Zoom');
    }
    else {
      $element['force']['#title'] = $this->t('Force Map Zoom');
    }

    if ($this instanceof ViewsPluginInterface) {
      $element['center'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Map Center'),
        'lat' => [
          '#title' => $this->t('Latitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#default_value' => $map_position_options['center']['lat'],
          '#required' => FALSE,
        ],
        'lon' => [
          '#title' => $this->t('Longitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#default_value' => $map_position_options['center']['lon'],
          '#required' => FALSE,
        ],
      ];
    }

    $element['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 18,
      '#default_value' => $map_position_options['zoom'],
      '#required' => TRUE,
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    ];

    if ($this instanceof ViewsPluginInterface) {
      $element['zoom']['#description'] = $this->t('These setting will be applied (anyway) to a single Marker Map.');
    }

    $element['minZoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 18,
      '#default_value' => $map_position_options['minZoom'],
      '#required' => TRUE,
    ];

    $element['maxZoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 18,
      '#default_value' => $map_position_options['maxZoom'],
      '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Generate the Leaflet Icon Form Element.
   *
   * @param array $icon_options
   *   The icon array definition.
   *
   * @return array
   *   The Leaflet Icon Form Element.
   */
  protected function generateIconFormElement(array $icon_options) {

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Icon'),
      'description' => [
        '#markup' => $this->t('For details on the following setup refer to @leaflet_icon_documentation_link', [
          '@leaflet_icon_documentation_link' => $this->leafletService->leafletIconDocumentationLink(),
        ]),
      ],
    ];

    $element['iconUrl'] = [
      '#title' => $this->t('Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($icon_options['iconUrl']) ? $icon_options['iconUrl'] : NULL,
      '#element_validate' => [[$this, 'validateUrl']],
    ];

    $element['shadowUrl'] = [
      '#title' => $this->t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => isset($icon_options['shadowUrl']) ? $icon_options['shadowUrl'] : NULL,
      '#element_validate' => [[$this, 'validateUrl']],
    ];

    $element['iconSize'] = [
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('Size of the icon image in pixels.'),
    ];

    $element['iconSize']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconSize']) ? $icon_options['iconSize']['x'] : NULL,
    ];

    $element['iconSize']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconSize']) ? $icon_options['iconSize']['y'] : NULL,
    ];

    $element['iconAnchor'] = [
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.'),
    ];

    $element['iconAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconAnchor']) ? $icon_options['iconAnchor']['x'] : NULL,
    ];

    $element['iconAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconAnchor']) ? $icon_options['iconAnchor']['y'] : NULL,
    ];

    $element['shadowAnchor'] = [
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the shadow is shown.'),
    ];

    $element['shadowAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowAnchor']) ? $icon_options['shadowAnchor']['x'] : NULL,
    ];

    $element['shadowAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowAnchor']) ? $icon_options['shadowAnchor']['y'] : NULL,
    ];

    $element['popupAnchor'] = [
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the marker popup opens, relative to the anchor point.'),
    ];

    $element['popupAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['popupAnchor']) ? $icon_options['popupAnchor']['x'] : NULL,
    ];

    $element['popupAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['popupAnchor']) ? $icon_options['popupAnchor']['y'] : NULL,
    ];

    return $element;
  }

  /**
   * Set Map additional map Settings.
   *
   * @param array $map
   *   The map object.
   * @param array $options
   *   The options from where to set additional options.
   */
  protected function setAdditionalMapOptions(array &$map, array $options) {
    // Add additional settings to the Map.
    $map['settings']['map_position_force'] = isset($options['map_position']['force']) ? $options['map_position']['force'] : 0;
    $map['settings']['zoom'] = isset($options['map_position']['zoom']) ? (int) $options['map_position']['zoom'] : NULL;
    $map['settings']['minZoom'] = isset($options['map_position']['minZoom']) ? (int) $options['map_position']['minZoom'] : NULL;
    $map['settings']['maxZoom'] = isset($options['map_position']['maxZoom']) ? (int) $options['map_position']['maxZoom'] : NULL;
    $map['settings']['center'] = (isset($options['map_position']['center']['lat']) && isset($options['map_position']['center']['lon'])) ? [
      'lat' => floatval($options['map_position']['center']['lat']),
      'lng' => floatval($options['map_position']['center']['lon']),
    ] : NULL;
  }

  /**
   * Form element validation handler for a Map Zoom level.
   *
   * {@inheritdoc}
   */
  public static function zoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the initial map zoom level.
    $zoom = $element['#value'];
    $min_zoom = $values['minZoom'];
    $max_zoom = $values['maxZoom'];
    if ($zoom < $min_zoom || $zoom > $max_zoom) {
      $form_state->setError($element, t('The @zoom_field should be between the Minimum and the Maximum Zoom levels.', ['@zoom_field' => $element['#title']]));
    }
  }

  /**
   * Form element validation handler for the Map Max Zoom level.
   *
   * {@inheritdoc}
   */
  public static function maxZoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the max zoom level.
    $min_zoom = $values['minZoom'];
    $max_zoom = $element['#value'];
    if ($max_zoom && $max_zoom <= $min_zoom) {
      $form_state->setError($element, t('The Max Zoom level should be above the Minimum Zoom level.'));
    }
  }

  /**
   * Validate Url method.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function validateUrl(array $element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !UrlHelper::isValid($element['#value'])) {
      $form_state->setError($element, $this->t("Icon Url is not valid."));
    }
  }

}
