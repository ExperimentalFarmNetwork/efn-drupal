<?php

namespace Drupal\leaflet_views\Plugin\views\style\deprecated;

use Drupal\leaflet_views\Plugin\views\style\LeafletMap;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leafet_map",
 *   title = @Translation("Leaflet"),
 *   help = @Translation("Incorrect version of the Leaflet Map View Display (with typo into its machine name."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map",
 *   no_ui = TRUE
 * )
 */
class LeafletMapIncorrect extends LeafletMap {

}
