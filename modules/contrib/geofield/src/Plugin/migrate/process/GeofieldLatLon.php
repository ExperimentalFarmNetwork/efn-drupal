<?php

namespace Drupal\geofield\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\geofield\WktGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Process latitude and longitude and return the value for the D8 geofield.
 *
 * @MigrateProcessPlugin(
 *   id = "geofield_latlon"
 * )
 */
class GeofieldLatLon extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The WktGenerator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WktGeneratorInterface $wkt_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->wktGenerator = $wkt_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geofield.wkt_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = array_map('floatval', $value);
    list($lat, $lon) = $value;

    if (empty($lat) || empty($lon)) {
      return NULL;
    }

    return $this->wktGenerator->WktBuildPoint([$lon, $lat]);
  }

}
