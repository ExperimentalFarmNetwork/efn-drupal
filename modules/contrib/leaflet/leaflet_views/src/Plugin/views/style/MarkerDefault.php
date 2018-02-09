<?php

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Style plugin to render leaflet markers.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "leaflet_marker_default",
 *   title = @Translation("Markers"),
 *   help = @Translation("Render data as leaflet markers."),
 *   display_types = {"leaflet"},
 * )
 */
class MarkerDefault extends StylePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = FALSE;

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * This option only makes sense on style plugins without row plugins, like
   * for example table.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * Constructs a MarkerDefault instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The modules handler.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderGroupingSets($sets, $level = 0) {
    $output = [];
    foreach ($sets as $set) {
      if ($this->usesRowPlugin()) {
        foreach ($set['rows'] as $index => $row) {
          $this->view->row_index = $index;
          $set['rows'][$index] = $this->view->rowPlugin->render($row);
          $this->alterLeafletMarkerPoints($set['rows'][$index], $row);
          if (!$set['rows'][$index]) {
            unset($set['rows'][$index]);
          }
        }
      }
      $set['features'] = [];
      foreach ($set['rows'] as $group) {
        $set['features'] = array_merge($set['features'], $group);
      }

      // Abort if we haven't managed to build any features.
      if (empty($set['features'])) {
        continue;
      }

      if ($feature_group = $this->renderLeafletGroup($set['group'], $level, $set['features'])) {
        // Allow modules to adjust the feature group.
        $this->moduleHandler->alter('leaflet_views_feature_group', $feature_group, $this);

        // If the rendered "feature group" is actually only a list of features,
        // merge them into the output; else simply append the feature group.
        if (empty($feature_group['group'])) {
          $output = array_merge($output, $feature_group['features']);
        }
        else {
          $output[] = $feature_group;
        }
      }
    }
    unset($this->view->row_index);
    return $output;
  }

  /**
   * Alter the marker definition generated from the row plugin.
   *
   * @param array $points
   *   The Marker Points.
   * @param \Drupal\views\ResultRow $row
   *   The Result rows.
   */
  protected function alterLeafletMarkerPoints(array &$points, ResultRow $row) {
  }

  /**
   * Render a single group of leaflet markers.
   *
   * @param string $title
   *   The group title.
   * @param string $level
   *   The current group level.
   * @param array $features
   *   The list of leaflet features / points.
   *
   * @return array
   *   Definition of leaflet markers, compatible with
   *   \Drupal::service('leaflet.service')->leafletRenderMap().
   */
  protected function renderLeafletGroup($title, $level, array $features = []) {
    return [
      'group' => FALSE,
      'features' => $features,
    ];
  }

}
