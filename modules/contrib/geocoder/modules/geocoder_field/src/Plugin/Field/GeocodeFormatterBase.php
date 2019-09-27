<?php

namespace Drupal\geocoder_field\Plugin\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geocoder\DumperPluginManager;
use Drupal\geocoder\Geocoder;
use Drupal\geocoder\ProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Base Plugin implementation of the Geocode formatter.
 */
abstract class GeocodeFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The geocoder service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $geocoder;

  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * The dumper plugin manager service.
   *
   * @var \Drupal\geocoder\DumperPluginManager
   */
  protected $dumperPluginManager;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;


  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * Constructs a GeocodeFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\geocoder\Geocoder $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The dumper plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    Geocoder $geocoder,
    ProviderPluginManager $provider_plugin_manager,
    DumperPluginManager $dumper_plugin_manager,
    RendererInterface $renderer,
    LinkGeneratorInterface $link_generator
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config_factory->get('geocoder.settings');
    $this->geocoder = $geocoder;
    $this->providerPluginManager = $provider_plugin_manager;
    $this->dumperPluginManager = $dumper_plugin_manager;
    $this->renderer = $renderer;
    $this->link = $link_generator;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.provider'),
      $container->get('plugin.manager.geocoder.dumper'),
      $container->get('renderer'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'dumper' => 'wkt',
      'plugins' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    // Attach Geofield Map Library.
    $element['#attached']['library'] = [
      'geocoder/general',
    ];

    // Get the enabled/selected plugins.
    $enabled_plugins = [];
    foreach ($this->getSetting('plugins') as $plugin_id => $plugin) {
      if ($plugin['checked']) {
        $enabled_plugins[] = $plugin_id;
      }
    }

    // Generates the Draggable Table of Selectable Geocoder Plugins.
    $element['plugins'] = $this->providerPluginManager->providersPluginsTableList($enabled_plugins);

    // Set a validation for the plugins selection.
    $element['plugins']['#element_validate'] = [[get_class($this), 'validatePluginsSettingsForm']];

    $element['dumper'] = [
      '#type' => 'select',
      '#weight' => 25,
      '#title' => 'Output format',
      '#default_value' => $this->getSetting('dumper'),
      '#options' => $this->dumperPluginManager->getPluginsAsOptions(),
      '#description' => t('Set the output format of the value. Ex, for a geofield, the format must be set to WKT.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $provider_plugin_ids = $this->getEnabledProviderPlugins();
    $dumper_plugins = $this->dumperPluginManager->getPluginsAsOptions();
    $dumper_plugin = $this->getSetting('dumper');

    // Replace the plugin array with its name.
    array_walk($provider_plugin_ids, function (&$item) {
      $item = $item['name'];
    });

    $summary[] = t('Geocoder plugin(s): @plugin_ids', [
      '@plugin_ids' => !empty($provider_plugin_ids) ? implode(', ', $provider_plugin_ids) : $this->t('Not set'),
    ]);

    $summary[] = t('Output format: @format', [
      '@format' => !empty($dumper_plugin) ? $dumper_plugins[$dumper_plugin] : $this->t('Not set'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
    $provider_plugins = $this->getEnabledProviderPlugins();

    foreach ($items as $delta => $item) {
      if ($address_collection = $this->geocoder->geocode($item->value, array_keys($provider_plugins))) {
        $elements[$delta] = [
          '#plain_text' => $dumper->dump($address_collection->first()),
        ];
      }
    }

    return $elements;
  }

  /**
   * Get the list of enabled Provider plugins.
   *
   * @return array
   *   Provider plugin IDs and their properties (id, name, arguments...).
   */
  public function getEnabledProviderPlugins() {
    $geocoder_plugins = $this->providerPluginManager->getPlugins();
    $plugins_settings = $this->getSetting('plugins');

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
    $plugins = array_filter($element['#value'], function ($value) {
      return isset($value['checked']) && TRUE == $value['checked'];
    });

    if (empty($plugins)) {
      $form_state->setError($element, t('The selected Geocode operation needs at least one plugin.'));
    }
  }

}
