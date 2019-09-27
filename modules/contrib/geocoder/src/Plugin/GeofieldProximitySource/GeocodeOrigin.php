<?php

namespace Drupal\geocoder\Plugin\GeofieldProximitySource;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Plugin\GeofieldProximitySourceBase;
use Drupal\geocoder\Geocoder;
use Drupal\geocoder\ProviderPluginManager;
use Geocoder\Model\AddressCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'Geocode Origin' proximity source plugin.
 *
 * @GeofieldProximitySource(
 *   id = "geofield_geocode_origin",
 *   label = @Translation("Geocode Origin"),
 *   description = @Translation("Geocodes origin from free text input."),
 *   exposedDescription = @Translation("Geocode origin from free text input."),
 *   context = {},
 * )
 */
class GeocodeOrigin extends GeofieldProximitySourceBase implements ContainerFactoryPluginInterface {

  /**
   * The Geocoder Service.
   *
   * @var \Drupal\geocoder\Geocoder
   */
  protected $geocoder;

  /**
   * The Providers Plugin Manager.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * Geocoder Plugins not compatible with Geofield Proximity Geocoding.
   *
   * @var array
   */
  protected $incompatiblePlugins = [
    'file',
    'gpxfile',
    'kmlfile',
    'geojsonfile',
  ];

  /**
   * The origin address to geocode and measure proximity from.
   *
   * @var array
   */
  protected $originAddress;

  /**
   * Constructs a GeocodeOrigin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geocoder\Geocoder $geocoder
   *   The Geocoder Service.
   * @param \Drupal\geocoder\ProviderPluginManager $providerPluginManager
   *   The Providers Plugin Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Geocoder $geocoder, ProviderPluginManager $providerPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->originAddress = isset($configuration['origin_address']) ? $configuration['origin_address'] : '';
    $this->geocoder = $geocoder;
    $this->providerPluginManager = $providerPluginManager;
    $this->origin = $this->getAddressOrigin($this->originAddress);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.provider')
    );
  }

  /**
   * Geocode the Origin Address.
   *
   * @param string $address
   *   The String address to Geocode.
   *
   * @return array
   *   The Origin array.
   */
  protected function getAddressOrigin($address) {
    $origin = [
      'lat' => '',
      'lon' => '',
    ];

    if (!empty($address)) {
      // Try static geocoding cache.
      $cache = &drupal_static("geocoder_proximity_cache:$address", NULL);
      if (is_array($cache) && array_key_exists('lat', $cache) && array_key_exists('lon', $cache)) {
        return $cache;
      }

      $provider_plugins = $this->getEnabledProviderPlugins();

      // Try geocoding and extract coordinates of the first match.
      $address_collection = $this->geocoder->geocode($address, array_keys($provider_plugins));
      if ($address_collection instanceof AddressCollection && count($address_collection) > 0) {
        $address = $address_collection->get(0);
        $coordinates = $address->getCoordinates();
        $origin = [
          'lat' => $coordinates->getLatitude(),
          'lon' => $coordinates->getLongitude(),
        ];
      }
      $cache = $origin;
    }
    return $origin;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents, $is_exposed = FALSE) {

    $form['origin_address'] = [
      '#title' => t('Origin'),
      '#type' => 'textfield',
      '#description' => t('Address, City, Zip-Code, Country, ...'),
      '#default_value' => $this->originAddress,
    ];

    if (!$is_exposed) {

      // Attach Geofield Map Library.
      $form['#attached']['library'] = [
        'geocoder/general',
      ];

      $plugins_settings = isset($this->configuration['plugins']) ? $this->configuration['plugins'] : [];

      // Get the enabled/selected plugins.
      $enabled_plugins = [];
      foreach ($plugins_settings as $plugin_id => $plugin) {
        if (!empty($plugin['checked'])) {
          $enabled_plugins[] = $plugin_id;
        }
      }

      // Generates the Draggable Table of Selectable Geocoder Plugins.
      $form['plugins'] = $this->providerPluginManager->providersPluginsTableList($enabled_plugins);

      // Filter out the Geocoder Plugins that are not compatible with Geofield
      // Proximity Geocoding.
      $form['plugins'] = array_filter($form['plugins'], function ($e) {
        return !in_array($e, $this->incompatiblePlugins);
      }, ARRAY_FILTER_USE_KEY);

      // Set a validation for the plugins selection.
      $form['plugins']['#element_validate'] = [[get_class($this), 'validatePluginsSettingsForm']];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(array &$form, FormStateInterface $form_state, array $options_parents) {
    $user_input = $form_state->getUserInput();
    if (!empty($user_input['options']['source_configuration']['origin_address'])) {
      $this->origin = $this->getAddressOrigin($user_input['options']['source_configuration']['origin_address']);
    }
  }

  /**
   * Get the list of enabled Provider plugins.
   *
   * @return array
   *   Provider plugin IDs and their properties (id, name, arguments...).
   */
  public function getEnabledProviderPlugins() {
    $geocoder_plugins = $this->providerPluginManager->getPlugins();
    $plugins_settings = isset($this->configuration['plugins']) ? $this->configuration['plugins'] : [];

    // Filter out unchecked plugins.
    $provider_plugin_ids = array_filter($plugins_settings, function ($plugin) {
      return isset($plugin['checked']) && $plugin['checked'] == TRUE;
    });

    $provider_plugin_ids = array_combine(array_keys($provider_plugin_ids), array_keys($provider_plugin_ids));

    foreach ($geocoder_plugins as $plugin) {
      if (isset($provider_plugin_ids[$plugin['id']])) {
        $provider_plugin_ids[$plugin['id']] = $plugin;
      }
    }

    return $provider_plugin_ids;
  }

  /**
   * {@inheritdoc}
   */
  public static function validatePluginsSettingsForm(array $element, FormStateInterface &$form_state) {
    $plugins = is_array($element['#value']) ? array_filter($element['#value'], function ($value) {
      return isset($value['checked']) && TRUE == $value['checked'];
    }) : [];

    if (empty($plugins)) {
      $form_state->setError($element, t('The Geocode Origin option needs at least one geocoder plugin selected.'));
    }
  }

}
