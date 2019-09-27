<?php

namespace Drupal\geocoder_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocoder\DumperPluginManager;
use Drupal\geocoder\Geocoder;
use Drupal\geocoder\ProviderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\geocoder_field\Plugin\Field\GeocodeFormatterBase;
use Drupal\geocoder_field\PreprocessorPluginManager;

/**
 * Plugin implementation of the Geocode formatter for File and Image fields.
 *
 * @FieldFormatter(
 *   id = "geocoder_geocode_formatter_file",
 *   label = @Translation("Geocode File/Image Gps Exif"),
 *   field_types = {
 *     "file",
 *     "image",
 *   },
 *   description =
 *   "Extracts valid GPS Exif data from the file/image (if existing)"
 * )
 */
class FileGeocodeFormatter extends GeocodeFormatterBase {

  /**
   * The Preprocessor Manager.
   *
   * @var \Drupal\geocoder_field\PreprocessorPluginManager
   */
  protected $preprocessorManager;

  /**
   * Unique Geocoder Plugin used by this formatter.
   *
   * @var string
   */
  protected $formatterPlugin = 'file';

  /**
   * Constructs a GeocodeFormatterFile object.
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
   *   The gecoder service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The dumper plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\geocoder_field\PreprocessorPluginManager $preprocessor_manager
   *   The Preprocessor Manager.
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
    LinkGeneratorInterface $link_generator,
    PreprocessorPluginManager $preprocessor_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $label,
      $view_mode,
      $third_party_settings,
      $config_factory,
      $geocoder,
      $provider_plugin_manager,
      $dumper_plugin_manager,
      $renderer,
      $link_generator
    );
    $this->preprocessorManager = $preprocessor_manager;
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
      $container->get('link_generator'),
      $container->get('plugin.manager.geocoder.preprocessor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['intro'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->pluginDefinition['description'],
    ];
    $element += parent::settingsForm($form, $form_state);
    $element['plugins'] = [
      $this->formatterPlugin => [
        'checked' => [
          '#type' => 'value',
          '#value' => TRUE,
        ],
      ],
    ];
    $element['plugin_info'] = [
      '#type' => 'item',
      '#title' => 'Plugin',
      '#markup' => $this->formatterPlugin,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $dumper_plugins = $this->dumperPluginManager->getPluginsAsOptions();
    $dumper_plugin = $this->getSetting('dumper');

    $summary['intro'] = $this->pluginDefinition['description'];
    $summary['plugins'] = t('Geocoder plugin(s): @formatterPlugin', [
      '@formatterPlugin' => $this->formatterPlugin,
    ]);

    $summary['dumper'] = t('Output format: @format', [
      '@format' => !empty($dumper_plugin) ? $dumper_plugins[$dumper_plugin] : $this->t('Not set'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    try {
      /* @var \Drupal\geocoder\DumperInterface $dumper */
      $dumper = $this->dumperPluginManager->createInstance($this->getSetting('dumper'));
      /* @var \Drupal\geocoder_field\PreprocessorInterface $preprocessor */
      $preprocessor = $this->preprocessorManager->createInstance('file');
      $preprocessor->setField($items)->preprocess();
      foreach ($items as $delta => $item) {
        if ($address_collection = $this->geocoder->geocode($item->value, [$this->formatterPlugin])) {
          $elements[$delta] = [
            '#markup' => $dumper->dump($address_collection->first()),
          ];
        }
      }
    }
    catch (\Exception $e) {
      watchdog_exception('geocoder', $e);
    }

    return $elements;
  }

}
