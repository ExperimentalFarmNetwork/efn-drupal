<?php

namespace Drupal\leaflet;

use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;

/**
 * Provides a  LeafletService class.
 */
class LeafletService {

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * GeofieldMapWidget constructor.
   *
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    GeoPHPInterface $geophp_wrapper,
    ModuleHandlerInterface $module_handler
  ) {
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Load all Leaflet required client files and return markup for a map.
   *
   * @param array $map
   *   The map settings array.
   * @param array $features
   *   The features array.
   * @param string $height
   *   The height value string.
   *
   * @return array
   *   The leaflet_map render array.
   */
  public function leafletRenderMap(array $map, array $features = [], $height = '400px') {
    $map_id = Html::getUniqueId('leaflet_map');

    $settings[$map_id] = [
      'mapId' => $map_id,
      'map' => $map,
      // JS only works with arrays, make sure we have one with numeric keys.
      'features' => array_values($features),
    ];
    return [
      '#theme' => 'leaflet_map',
      '#map_id' => $map_id,
      '#height' => $height,
      '#map' => $map,
      '#attached' => [
        'library' => ['leaflet/leaflet-drupal'],
        'drupalSettings' => [
          'leaflet' => $settings,
        ],
      ],
    ];
  }

  /**
   * Get all available Leaflet map definitions.
   *
   * @param string $map
   *   The specific map definition string.
   *
   * @return array
   *   The leaflet maps definition array.
   */
  public function leafletMapGetInfo($map = NULL) {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['leaflet_map_info'] = &drupal_static(__FUNCTION__);
    }
    $map_info = &$drupal_static_fast['leaflet_map_info'];

    if (empty($map_info)) {
      if ($cached = \Drupal::cache()->get('leaflet_map_info')) {
        $map_info = $cached->data;
      }
      else {
        $map_info = $this->moduleHandler->invokeAll('leaflet_map_info');

        // Let other modules alter the map info.
        $this->moduleHandler->alter('leaflet_map_info', $map_info);

        \Drupal::cache()->set('leaflet_map_info', $map_info);
      }
    }

    if (empty($map)) {
      return $map_info;
    }
    else {
      return isset($map_info[$map]) ? $map_info[$map] : [];
    }

  }

  /**
   * Convert a geofield into an array of map points.
   *
   * The map points can then be fed into $this->leafletRenderMap().
   *
   * @param mixed $items
   *   A single value or array of geo values, each as a string in any of the
   *   supported formats or as an array of $item elements, each with a
   *   $item['wkt'] field.
   *
   * @return array
   *   The return array.
   */
  public function leafletProcessGeofield($items = []) {

    if (!is_array($items)) {
      $items = [$items];
    }
    $data = [];
    foreach ($items as $item) {
      // Auto-detect and parse the format (e.g. WKT, JSON etc).
      /* @var \GeometryCollection $geom */
      if (!($geom = $this->geoPhpWrapper->load(isset($item['wkt']) ? $item['wkt'] : $item))) {
        continue;
      }
      $datum = ['type' => strtolower($geom->geometryType())];

      switch ($datum['type']) {
        case 'point':
          $datum += [
            'lat' => $geom->getY(),
            'lon' => $geom->getX(),
          ];
          break;

        case 'linestring':
          $components = $geom->getComponents();
          /* @var \Geometry $component */
          foreach ($components as $component) {
            $datum['points'][] = [
              'lat' => $component->getY(),
              'lon' => $component->getX(),
            ];
          }
          break;

        case 'polygon':
          /* @var \Collection[] $tmp */
          $tmp = $geom->getComponents();
          $components = $tmp[0]->getComponents();
          /* @var \Geometry $component */
          foreach ($components as $component) {
            $datum['points'][] = [
              'lat' => $component->getY(),
              'lon' => $component->getX(),
            ];
          }
          break;

        case 'multipolygon':
        case 'multipolyline':
        case 'multilinestring':
          if ($datum['type'] == 'multilinestring') {
            $datum['type'] = 'multipolyline';
          }
          if ($datum['type'] == 'multipolygon') {
            $tmp = $geom->getComponents();
            $components = $tmp[0]->getComponents();
          }
          else {
            $components = $geom->getComponents();
          }
          foreach ($components as $key => $component) {
            /* @var \GeometryCollection $component */
            $subcomponents = $component->getComponents();
            /* @var \Geometry $subcomponent */
            foreach ($subcomponents as $subcomponent) {
              $datum['component'][$key]['points'][] = [
                'lat' => $subcomponent->getY(),
                'lon' => $subcomponent->getX(),
              ];
            }
            unset($subcomponent);
          }
          break;
      }
      $data[] = $datum;
    }
    return $data;
  }

}
