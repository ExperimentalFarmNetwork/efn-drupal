<?php

namespace Drupal\geofield\Plugin\GeofieldBackend;

use Drupal\geofield\Plugin\GeofieldBackendBase;

/**
 * Default backend for Geofield.
 *
 * @GeofieldBackend(
 *   id = "geofield_backend_default",
 *   admin_label = @Translation("Default Backend")
 * )
 */

// @TODO: Document.

class GeofieldBackendDefault extends GeofieldBackendBase {

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return array(
      'type' => 'blob',
      'size' => 'big',
      'not null' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save($geometry) {
    $geom = \Drupal::service('geofield.geophp')->load($geometry);
    return $geom->out('wkt');
  }

  /**
   * {@inheritdoc}
   */
  public function load($value) {
    return \Drupal::service('geofield.geophp')->load($value);
  }
}
