<?php

namespace Drupal\geofield\Plugin\GeofieldBackend;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geofield\Plugin\GeofieldBackendBase;
use Drupal\geofield\GeoPHP\GeoPHPInterface;

/**
 * Default backend for Geofield.
 *
 * @GeofieldBackend(
 *   id = "geofield_backend_default",
 *   admin_label = @Translation("Default Backend")
 * )
 */
class GeofieldBackendDefault extends GeofieldBackendBase implements ContainerFactoryPluginInterface {

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * Constructs the GeofieldBackendDefault.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID for the migration process to do.
   * @param mixed $plugin_definition
   *   The configuration for the plugin.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GeoPHPInterface $geophp_wrapper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->geoPhpWrapper = $geophp_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geofield.geophp')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function schema() {
    return [
      'type' => 'blob',
      'size' => 'big',
      'not null' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save($geometry) {
    /* @var \Geometry $geom */
    $geom = $this->geoPhpWrapper->load($geometry);
    return $geom->out('wkt');
  }

  /**
   * {@inheritdoc}
   */
  public function load($value) {
    return $this->geoPhpWrapper->load($value);
  }

}
