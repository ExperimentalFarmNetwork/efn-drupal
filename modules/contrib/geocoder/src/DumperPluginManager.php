<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\Annotation\GeocoderDumper;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides a plugin manager for geocoder dumpers.
 */
class DumperPluginManager extends GeocoderPluginManagerBase {

  private $maxLengthFieldTypes = [
    "text",
    "string",
  ];

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct('Plugin/Geocoder/Dumper', $namespaces, $module_handler, DumperInterface::class, GeocoderDumper::class);
    $this->alterInfo('geocoder_dumper_info');
    $this->setCacheBackend($cache_backend, 'geocoder_dumper_plugins');
    $this->logger = $logger_factory;
  }

  /**
   * Define an Address field value from a Geojson string.
   *
   * @param string $geojson
   *   The GeoJson place string.
   *
   * @return array
   *   An array of the Address field value.
   */
  public function setAddressFieldFromGeojson($geojson) {
    $geojson_array = Json::decode($geojson);

    $country_code = $this->setCountryFromGeojson($geojson);

    $geojson_array['properties'] += [
      'streetName' => '',
      'postalCode' => '',
      'locality' => '',
    ];

    return [
      'country_code' => $country_code,
      'address_line1' => $geojson_array['properties']['streetName'],
      'postal_code' => $geojson_array['properties']['postalCode'],
      'locality' => $geojson_array['properties']['locality'],
    ];
  }

  /**
   * Define a Country value from a Geojson string.
   *
   * @param string $geojson
   *   The GeoJson place string.
   *
   * @return string
   *   A country code.
   */
  public function setCountryFromGeojson($geojson) {
    $geojson_array = Json::decode($geojson);

    $country_code = isset($geojson_array['properties']['countryCode']) ? strtoupper(substr($geojson_array['properties']['countryCode'], 0, 2)) : NULL;

    // Some provider (like MapQuest) might not return the countryCode but just
    // the country name, so try to convert it into countryCode, as it seems to
    // be mandatory in Address Field Entity API.
    if (!isset($country_code)) {
      $country_code = isset($geojson_array['properties']['country']) ? strtoupper(substr($geojson_array['properties']['country'], 0, 2)) : '';
    }

    return $country_code;
  }

  /**
   * Check|Fix some incompatibility between Dumper output and Field Config.
   *
   * @param string $dumper_result
   *   The Dumper result string.
   * @param \Drupal\geocoder\DumperInterface|\Drupal\Component\Plugin\PluginInspectionInterface $dumper
   *   The Dumper.
   * @param \Drupal\Core\Field\FieldConfigInterface $field_config
   *   The Field Configuration.
   */
  public function fixDumperFieldIncompatibility(&$dumper_result, $dumper, FieldConfigInterface $field_config) {
    // Fix not UTF-8 encoded result strings.
    // https://stackoverflow.com/questions/6723562/how-to-detect-malformed-utf-8-string-in-php
    if (is_string($dumper_result)) {
      if (!preg_match('//u', $dumper_result)) {
        $dumper_result = utf8_encode($dumper_result);
      }
    }

    // If the field is a string|text type check if the result length is
    // compatible with its max_length definition, otherwise truncate it and
    // set | log a warning message.
    if (in_array($field_config->getType(), $this->maxLengthFieldTypes) &&
      strlen($dumper_result) > $field_config->getFieldStorageDefinition()->getSetting('max_length')) {

      $incompatibility_warning_message = t("The '@field_name' field 'max length' property is not compatible with the chosen '@dumper' dumper.<br>Thus <b>be aware</b> <u>the dumper output result has been truncated to @max_length chars (max length)</u>.<br> You are advised to change the '@field_name' field definition or chose another compatible dumper.", [
        '@field_name' => $field_config->getLabel(),
        '@dumper' => $dumper->getPluginId(),
        '@max_length' => $field_config->getFieldStorageDefinition()->getSetting('max_length'),
      ]);

      $dumper_result = substr($dumper_result, 0, $field_config->getFieldStorageDefinition()->getSetting('max_length'));

      // Display a max-length incompatibility warning message.
      drupal_set_message($incompatibility_warning_message, 'warning');

      // Log the max-length incompatibility.
      $this->logger->get('geocoder')->warning($incompatibility_warning_message);
    }
  }

}
