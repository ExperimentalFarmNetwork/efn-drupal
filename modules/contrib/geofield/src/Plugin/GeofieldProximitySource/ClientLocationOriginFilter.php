<?php

namespace Drupal\geofield\Plugin\GeofieldProximitySource;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Defines 'Geofield Client Location Origin' plugin.
 *
 * @package Drupal\geofield\Plugin
 *
 * @GeofieldProximitySource(
 *   id = "geofield_client_location_origin",
 *   label = @Translation("Client Location Origin"),
 *   description = @Translation("Gets the Client Location through the browser HTML5 Geolocation API."),
 *   context = {
 *     "filter",
 *   },
 *   exposedOnly = true
 * )
 */
class ClientLocationOriginFilter extends ManualOriginDefault {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents, $is_exposed = FALSE) {

    $form['#attributes'] = [
      'class' => ['proximity-origin-client'],
    ];

    $form["origin"] = [
      '#title' => t('Client Coordinates'),
      '#type' => 'geofield_latlon',
      '#description' => t('Value in decimal degrees. Use dot (.) as decimal separator.'),
      '#default_value' => [
        'lat' => '',
        'lon' => '',
      ],
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    // If it is a proximity filter context and IS NOT exposed, render origin
    // summary option.
    if ($this->viewHandler->configuration['id'] == 'geofield_proximity_filter' && !$is_exposed) {

      $form['origin_summary_flag'] = [
        '#type' => 'checkbox',
        '#title' => t('Show the Client Origin coordinates as summary in the Exposed Form'),
        '#default_value' => isset($this->configuration['origin_summary_flag']) ? $this->configuration['origin_summary_flag'] : TRUE,
      ];
    }

    // If it IS exposed load the geolocation library.
    if ($is_exposed) {
      $form['origin']['#attached']['library'][] = 'geofield/geolocation';

      // And eventually Render the Origin Summary.
      if (isset($this->configuration['origin_summary_flag']) && $this->configuration['origin_summary_flag']) {
        $form['origin_summary'] = [
          "#type" => 'html_tag',
          "#tag" => 'div',
          '#value' => $this->t('from Latitude: @lat and Longitude: @lon.', [
            '@lat' => new FormattableMarkup('<span class="geofield-lat-summary">@lat</span>', [
              '@lat' => $this->t('undefined'),
            ]),
            '@lon' => new FormattableMarkup('<span class="geofield-lon-summary">@lon</span>', [
              '@lon' => $this->t('undefined'),
            ]),
          ]),
        ];
      }
    }
  }

}
