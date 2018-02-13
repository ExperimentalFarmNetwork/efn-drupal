<?php

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\geofield\WktGeneratorInterface;

/**
 * Abstract class for Geofield widgets.
 */
abstract class GeofieldBaseWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The WKT format Generator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * GeofieldBaseWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\geofield\WktGeneratorInterface $wkt_generator
   *   The WKT format Generator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    GeoPHPInterface $geophp_wrapper,
    WktGeneratorInterface $wkt_generator
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->wktGenerator = $wkt_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('geofield.geophp'),
      $container->get('geofield.wkt_generator')
    );
  }

}
