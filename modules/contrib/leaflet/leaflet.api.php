<?php

/**
 * @file
 * API documentation for Administration menu.
 */

use Drupal\views\ResultRow;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\leaflet_views\Plugin\views\style\LeafletMap;

/**
 * Define map definitions to be used when rendering a map.
 *
 * The leaflet_map_get_info() will grab every defined map, and the returned
 * associative array is then passed to
 * \Drupal::service('leaflet.service')->leafletRenderMap(), along with a
 * collection of features.
 *
 * The settings array maps to the settings available to the leaflet map object,
 * see http://leafletjs.com/reference.html#map-property.
 *
 * Layers are the available base layers for the map and, if you enable the
 * layer control, can be toggled on the map.
 *
 * @return array
 *   The definitions array.
 */
function hook_leaflet_map_info() {
  return [
    'OSM Mapnik' => [
      'label' => 'OSM Mapnik',
      'description' => t('Leaflet default map.'),
      'settings' => [
        'dragging' => TRUE,
        'touchZoom' => TRUE,
        'scrollWheelZoom' => TRUE,
        'doubleClickZoom' => TRUE,
        'zoomControl' => TRUE,
        'attributionControl' => TRUE,
        'trackResize' => TRUE,
        'fadeAnimation' => TRUE,
        'zoomAnimation' => TRUE,
        'closePopupOnClick' => TRUE,
        // Sets the map min max and starting zoom,
        // 'minZoom' => 10,
        // 'maxZoom' => 15,
        // 'zoom' => 15,
        //
        // Specific of the Drupal Leaflet module
        // Enables Layer Control in case of multiple layers, and add options.
        'layerControl' => TRUE,
        'layerControlOptions' => [
          'position' => 'topright',
        ],
      ],
      'layers' => [
        'earth' => [
          'urlTemplate' => '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
          'options' => [
            'attribution' => 'OSM Mapnik',
          ],
        ],
      ],
      // Uncomment the lines below to use a custom path style for geometries.
      /*'path' => [
        "color" => "black",
        "opacity" => "0.8",
        "stroke" => 2,
        "fill" => TRUE,
        "fillColor" => "blue",
        "fillOpacity" => "0.1",
      ],*/
      // Uncomment the lines below to use a custom icon.
      /*'icon' => array(
        'iconUrl'       => '/sites/default/files/icon.png',
        'iconSize'      => array('x' => '20', 'y' => '40'),
        'iconAnchor'    => array('x' => '20', 'y' => '40'),
        'popupAnchor'   => array('x' => '-8', 'y' => '-32'),
        'shadowUrl'     => '/sites/default/files/icon-shadow.png',
        'shadowSize'    => array('x' => '25', 'y' => '27'),
        'shadowAnchor'  => array('x' => '0', 'y' => '27'),
      ),*/
      // Enable and configure plugins in the plugins array.
      'plugins' => [],
    ],
  ];
}

/**
 * Alters the map definitions defined by hook_leaflet_map_info().
 *
 * The settings array maps to the settings available to the leaflet map object,
 * http://leafletjs.com/reference.html#map-properties.
 *
 * @param array $map_info
 *   Map info array.
 */
function hook_leaflet_map_info_alter(array &$map_info) {
  // Set a custom iconUrl for the default map type.
  $map_info['OSM Mapnik']['icon']['iconUrl'] = '/sites/default/files/icon.png';
}

/**
 * Alter Leaflet View Style Point / Marker definition.
 *
 * @param array $point
 *   The Point / Marker definition.
 * @param \Drupal\views\ResultRow $result
 *   The view row result.
 * @param \Drupal\views\Plugin\views\row\RowPluginBase $rowPlugin
 *   The rowPlugin.
 * */
function hook_leaflet_views_feature_alter(array &$point, ResultRow &$result, RowPluginBase $rowPlugin) {
  // Make custom alterations to $point, eventually using others contexts
  // definitions..
}

/**
 * Alter the Leaflet Map Default Formatter settings.
 *
 * Allow other modules to add/alter the map js settings.
 *
 * @param array $map_settings
 *   The array of geofield map element settings.
 * @param \Drupal\Core\Field\FieldItemListInterface $items
 *   The field values to be rendered.
 * */
function hook_leaflet_default_map_formatter_alter(array &$map_settings, FieldItemListInterface &$items) {
  // Make custom alterations to $map_settings, eventually using the $items
  // context.
}

/**
 * Alter the Leaflet Map View Style settings.
 *
 * Allow other modules to add/alter the map js settings.
 *
 * @param array $map_settings
 *   The array of geofield map element settings.
 * @param \Drupal\leaflet_views\Plugin\views\style\LeafletMap $view_style
 *   The Leaflet Map View Style.
 * */
function hook_leaflet_map_view_style_alter(array &$map_settings, LeafletMap &$view_style) {
  // Make custom alterations to $map_settings, eventually using the $view_style
  // context.
}
