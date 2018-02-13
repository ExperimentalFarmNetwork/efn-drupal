<?php

namespace Drupal\leaflet_views\Plugin\views\style;

/**
 * Style plugin to render leaflet features in layer groups.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "leaflet_marker_group",
 *   title = @Translation("Grouped Markers"),
 *   help = @Translation("Render data as leaflet markers, grouped in layers."),
 *   display_types = {"leaflet"},
 * )
 */
class MarkerLayerGroup extends MarkerDefault {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = TRUE;

  /**
   * {@inheritdoc}
   */
  public function renderGrouping($records, $groupings = [], $group_rendered = NULL) {
    $sets = parent::renderGrouping($records, $groupings, $group_rendered);
    if (!$groupings) {
      // Set group label to display label, if empty.
      $attachment_title = $this->view->getDisplay()->getOption('title');
      $sets['']['group'] = $attachment_title ? $attachment_title : $this->t('Label missing');
    }
    return $sets;
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLeafletGroup(array $features = [], $title = '', $level = 0) {
    return [
      'group' => TRUE,
      'label' => $title,
      'features' => $features,
    ];
  }

}
