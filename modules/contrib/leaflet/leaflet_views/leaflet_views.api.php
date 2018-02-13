<?php

/**
 * @file
 * Hook documentation for leaflet_views module.
 */

use Drupal\views\ResultRow;
use Drupal\leaflet_views\Plugin\views\row\LeafletMarker;
use Drupal\leaflet_views\Plugin\views\style\MarkerDefault;

/**
 * Adjust the array representing a leaflet feature/marker.
 *
 * @param array $feature
 *   The leaflet feature. Available keys are:
 *   - type: Indicates the type of feature (usually one of these: point,
 *     polygon, linestring, multipolygon, multipolyline).
 *   - popup: This value is displayed in a popup after the user clicks on the
 *     feature.
 *   - label: Not used at the moment.
 *   - Other possible keys include "lat", "lon", "points", "component",
 *     depending on feature type.
 *     {@see \Drupal::service('leaflet.service')->leafletProcessGeofield()}
 *     for details.
 * @param \Drupal\views\ResultRow $row
 *   The views result row.
 * @param \Drupal\leaflet_views\Plugin\views\row\LeafletMarker $rowPlugin
 *   The row plugin used for rendering the feature.
 */
function hook_leaflet_views_feature_alter(array &$feature, ResultRow $row, LeafletMarker $rowPlugin) {
}

/**
 * Adjust the array representing a leaflet feature group.
 *
 * @param array $group
 *   The leaflet feature group. Available keys are:
 *   - group: Indicates whether the contained features should be rendered as a
 *     layer group. Set to FALSE to render contained features ungrouped.
 *   - features: List of features contained in this group.
 *   - label: The group label, e.g. used for the layer control widget.
 * @param \Drupal\leaflet_views\Plugin\views\style\MarkerDefault $stylePlugin
 *   The style plugin used for rendering the feature group.
 */
function hook_leaflet_views_feature_group_alter(array &$group, MarkerDefault $stylePlugin) {
}
