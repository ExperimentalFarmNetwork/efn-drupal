<?php

namespace Drupal\leaflet;

use Drupal\Core\Session\AccountInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Component\Serialization\Json;

/**
 * Provides a  LeafletService class.
 */
class LeafletService {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * LeafletService constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(
    AccountInterface $current_user,
    GeoPHPInterface $geophp_wrapper,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator
  ) {
    $this->currentUser = $current_user;
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
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
    $map_id = isset($map['id']) ? $map['id'] : Html::getUniqueId('leaflet_map');
    $attached_libraries = ['leaflet/leaflet-drupal', 'leaflet/general'];
    // Add the Leaflet Fullscreen library, if requested.
    if (isset($map['settings']['fullscreen_control'])) {
      $attached_libraries[] = 'leaflet/leaflet.fullscreen';
    }
    // Add the Leaflet Markecluster library and functionalities, if requested.
    if ($this->moduleHandler->moduleExists('leaflet_markercluster') && isset($map['settings']['leaflet_markercluster']) && $map['settings']['leaflet_markercluster']['control']) {
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster';
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster-drupal';
    }

    // Add the Leaflet Geocoder library and functionalities, if requested,
    // and the user has access to Geocoder Api Enpoints.
    if ($this->moduleHandler->moduleExists('geocoder')
      && class_exists('\Drupal\geocoder\Controller\GeocoderApiEnpoints')
      && isset($map['settings']['geocoder'])
      && $map['settings']['geocoder']['control']
      && $this->currentUser->hasPermission('access geocoder api endpoints')) {
      $attached_libraries[] = 'leaflet/leaflet.geocoder';

      // Set the $map['settings']['geocoder']['providers'] as the enabled ones.
      $enabled_providers = [];
      foreach ($map['settings']['geocoder']['settings']['providers'] as $plugin_id => $plugin) {
        if (!empty($plugin['checked'])) {
          $enabled_providers[] = $plugin_id;
        }
      }
      $map['settings']['geocoder']['settings']['providers'] = $enabled_providers;
      $map['settings']['geocoder']['settings']['options'] = [
        'options' => JSON::decode($map['settings']['geocoder']['settings']['options']),
      ];
    }

    $settings[$map_id] = [
      'mapid' => $map_id,
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
        'library' => $attached_libraries,
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
      $data[] = $this->leafletProcessGeometry($geom);

    }
    return $data;
  }

  /**
   * Process the Geometry Collection.
   *
   * @param \Geometry $geom
   *   The Geometry Collection.
   *
   * @return array
   *   The return array.
   */
  private function leafletProcessGeometry(\Geometry $geom) {
    $datum = ['type' => strtolower($geom->geometryType())];

    switch ($datum['type']) {
      case 'point':
        $datum = [
          'type' => 'point',
          'lat' => $geom->getY(),
          'lon' => $geom->getX(),
        ];
        break;

      case 'linestring':
        /* @var \GeometryCollection $geom */
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
        /* @var \GeometryCollection $geom */
        $tmp = $geom->getComponents();
        /* @var \GeometryCollection $geom */
        $geom = $tmp[0];
        $components = $geom->getComponents();
        /* @var \Geometry $component */
        foreach ($components as $component) {
          $datum['points'][] = [
            'lat' => $component->getY(),
            'lon' => $component->getX(),
          ];
        }
        break;

      case 'multipolyline':
      case 'multilinestring':
        if ($datum['type'] == 'multilinestring') {
          $datum['type'] = 'multipolyline';
          $datum['multipolyline'] = TRUE;
        }
        /* @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        /* @var \GeometryCollection $component */
        foreach ($components as $key => $component) {
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

      case 'multipolygon':
        $components = [];
        /* @var \GeometryCollection $geom */
        $tmp = $geom->getComponents();
        /* @var \GeometryCollection $polygon */
        foreach ($tmp as $delta => $polygon) {
          $polygon_component = $polygon->getComponents();
          foreach ($polygon_component as $k => $linestring) {
            $components[] = $linestring;
          }
        }
        foreach ($components as $key => $component) {
          $subcomponents = $component->getComponents();
          /* @var \Geometry $subcomponent */
          foreach ($subcomponents as $subcomponent) {
            $datum['component'][$key]['points'][] = [
              'lat' => $subcomponent->getY(),
              'lon' => $subcomponent->getX(),
            ];
          }
        }
        break;

      case 'geometrycollection':
      case 'multipoint':
        /* @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        foreach ($components as $key => $component) {
          $datum['component'][$key] = $this->leafletProcessGeometry($component);
        }
        break;

    }
    return $datum;
  }

  /**
   * Pre Process the MapSettings.
   *
   * Performs some preprocess on the maps settings before sending to js.
   *
   * @param array $map_settings
   *   The map settings.
   */
  public function preProcessMapSettings(array &$map_settings) {
    // Generate correct Absolute iconUrl & shadowUrl, if not external.
    if (!empty($map_settings['icon']['iconUrl'])) {
      $map_settings['icon']['iconUrl'] = $this->pathToAbsolute($map_settings['icon']['iconUrl']);
    }
    if (!empty($map_settings['icon']['shadowUrl'])) {
      $map_settings['icon']['shadowUrl'] = $this->pathToAbsolute($map_settings['icon']['shadowUrl']);
    }
  }

  /**
   * Leaflet Icon Documentation Link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The Leaflet Icon Documentation Link.
   */
  public function leafletIconDocumentationLink() {
    return $this->link->generate(t('Leaflet Icon Documentation'), Url::fromUri('https://leafletjs.com/reference-1.3.0.html#icon', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));
  }

  /**
   * Generate an Absolute Url from a string Path.
   *
   * @param string $path
   *   The path string to generate.
   *
   * @return string
   *   The absolute $path
   */
  public function pathToAbsolute($path) {
    if (!UrlHelper::isExternal($path)) {
      $path = Url::fromUri('base:', ['absolute' => TRUE])->toString() . $path;
    }
    return $path;
  }

  /**
   * Check if an array has all values empty.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   The bool result.
   */
  public function multipleEmpty(array $array) {
    foreach ($array as $value) {
      if (empty($value)) {
        continue;
      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

}
