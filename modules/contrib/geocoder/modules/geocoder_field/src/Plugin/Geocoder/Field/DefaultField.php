<?php

namespace Drupal\geocoder_field\Plugin\Geocoder\Field;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\DumperPluginManager;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\geocoder_field\GeocoderFieldPluginInterface;
use Drupal\geocoder_field\GeocoderFieldPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityFieldManagerInterface;

/**
 * Provides a default generic geocoder field plugin.
 *
 * @GeocoderField(
 *   id = "default",
 *   label = @Translation("Generic geofield field plugin"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long",
 *   }
 * )
 */
class DefaultField extends PluginBase implements GeocoderFieldPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The plugin manager for this type of plugins.
   *
   * @var \Drupal\geocoder_field\GeocoderFieldPluginManager
   */
  protected $fieldPluginManager;

  /**
   * The dumper plugin manager service.
   *
   * @var \Drupal\geocoder\DumperPluginManager
   */
  protected $dumperPluginManager;

  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

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
   * Constructs a 'default' plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\geocoder_field\GeocoderFieldPluginManager $field_plugin_manager
   *   The plugin manager for this type of plugins.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The dumper plugin manager service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    GeocoderFieldPluginManager $field_plugin_manager,
    DumperPluginManager $dumper_plugin_manager,
    ProviderPluginManager $provider_plugin_manager,
    RendererInterface $renderer,
    LinkGeneratorInterface $link_generator,
    EntityFieldManagerInterface $entity_field_manager

  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('geocoder.settings');
    $this->moduleHandler = $module_handler;
    $this->fieldPluginManager = $field_plugin_manager;
    $this->dumperPluginManager = $dumper_plugin_manager;
    $this->providerPluginManager = $provider_plugin_manager;
    $this->renderer = $renderer;
    $this->link = $link_generator;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('geocoder_field.plugin.manager.field'),
      $container->get('plugin.manager.geocoder.dumper'),
      $container->get('plugin.manager.geocoder.provider'),
      $container->get('renderer'),
      $container->get('link_generator'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(FieldConfigInterface $field, array $form, FormStateInterface &$form_state) {

    $geocoder_settings_link = $this->link->generate(t('Edit options in the Geocoder configuration page</span>'), Url::fromRoute('geocoder.settings', [], [
      'query' => [
        'destination' => Url::fromRoute('<current>')
          ->toString(),
      ],
    ]));

    $element = [
      '#type' => 'details',
      '#title' => t('Geocode'),
      '#open' => TRUE,
    ];

    if ($this->config->get('geocoder_presave_disabled')) {
      $element['#description'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t("<b>The Geocoder and Reverse Geocoding operations are disabled, and won't be processed.</b> (@geocoder_settings_link)", [
          '@geocoder_settings_link' => $geocoder_settings_link,
        ]),
      ];
      $element['#open'] = FALSE;
    }

    // Attach Geofield Map Library.
    $element['#attached']['library'] = [
      'geocoder/general',
    ];

    $invisible_state = [
      'invisible' => [
        ':input[name="third_party_settings[geocoder_field][method]"]' => ['value' => 'none'],
      ],
    ];

    $element['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Geocode method'),
      '#options' => [
        'none' => $this->t('No geocoding'),
        'source' => $this->t('<b>Geocode</b> from an existing field'),
      ],
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'method', 'none'),
    ];

    $element['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('This is the weight order that will be followed for Geocode/Reverse Geocode operations on multiple fields of this entity. Lowest weights will be processed first.'),
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'weight', 0),
      '#min' => 0,
      '#max' => 9,
      '#size' => 2,
      '#states' => $invisible_state,
    ];

    // Set a default empty value for geocode_field.
    $element['geocode_field'] = [
      '#type' => 'value',
      '#value' => '',
    ];

    // Set a default empty value for reverse_geocode_field.
    $element['reverse_geocode_field'] = [
      '#type' => 'value',
      '#value' => '',
    ];

    // Get the field options for geocode and reverse geocode source fields.
    $geocode_source_fields_options = $this->fieldPluginManager->getGeocodeSourceFields($field->getTargetEntityTypeId(), $field->getTargetBundle(), $field->getName());
    $reverse_geocode_source_fields_options = $this->fieldPluginManager->getReverseGeocodeSourceFields($field->getTargetEntityTypeId(), $field->getTargetBundle(), $field->getName());

    // If there is at least one geocode source field defined from the entity,
    // extend the Form with Geocode Option.
    // (from Geofield) capabilities.
    if (!empty($geocode_source_fields_options)) {
      $element['geocode_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Geocode from an existing field'),
        '#description' => $this->t('Select which field you would like to use as Source Address field.'),
        '#default_value' => $field->getThirdPartySetting('geocoder_field', 'geocode_field'),
        '#options' => $geocode_source_fields_options,
        '#states' => [
          'visible' => [
            ':input[name="third_party_settings[geocoder_field][method]"]' => ['value' => 'source'],
          ],
        ],
      ];
    }

    // If the Geocoder Geofield Module exists and there is at least one
    // geofield defined from the entity, extend the Form with Reverse Geocode
    // (from Geofield) capabilities.
    if ($this->moduleHandler->moduleExists('geocoder_geofield') && !empty($reverse_geocode_source_fields_options)) {

      // Add the Option to Reverse Geocode.
      $element['method']['#options']['destination'] = $this->t('<b>Reverse Geocode</b> from a Geofield type existing field');

      // Add the Element to select the Reverse Geocode field.
      $element['reverse_geocode_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Reverse Geocode from an existing field'),
        '#description' => $this->t('Select which field you would like to use as Geographic Source field.'),
        '#default_value' => $field->getThirdPartySetting('geocoder_field', 'reverse_geocode_field'),
        '#options' => $reverse_geocode_source_fields_options,
        '#states' => [
          'visible' => [
            ':input[name="third_party_settings[geocoder_field][method]"]' => ['value' => 'destination'],
          ],
        ],
      ];
    }
    $element['hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Hide</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the Field will be Hidden to the user in the edit form, </br>and totally managed by the Geocode/Reverse Geocode operation chosen'),
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'hidden'),
      '#states' => $invisible_state,
    ];
    $element['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Disable</strong> this field in the Content Edit Form'),
      '#description' => $this->t('If checked, the Field will be Disabled to the user in the edit form, </br>and totally managed by the Geocode/Reverse Geocode operation chosen'),
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'disabled'),
      '#states' => $invisible_state,
    ];

    // Get the enabled/selected plugins.
    $enabled_plugins = (array) $field->getThirdPartySetting('geocoder_field', 'plugins');

    // Generates the Draggable Table of Selectable Geocoder Plugins.
    $element['plugins'] = $this->providerPluginManager->providersPluginsTableList($enabled_plugins);
    $element['plugins']['#states'] = $invisible_state;

    $element['dumper'] = [
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'dumper', 'wkt'),
      '#options' => $this->dumperPluginManager->getPluginsAsOptions(),
      '#description' => $this->t('Set the output format of the value. Ex, for a geofield, the format must be set to WKT.'),
      '#states' => $invisible_state,
    ];
    $element['delta_handling'] = [
      '#type' => 'select',
      '#title' => $this->t('Multi-value input handling'),
      '#description' => 'If the source field is a multi-value field, this is mapped 1-on-1 by default.
      That means that if you can add an unlimited amount of text fields, this also results in an
      unlimited amount of geocodes. However, if you have one field that contains multiple geocodes
      (like a file) you can select single-to-multiple to extract all geocodes from the first field.',
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'delta_handling', 'default'),
      '#options' => [
        'default' => $this->t('Match Multiples (default)'),
        's_to_m' => $this->t('Single to Multiple'),
      ],
      '#states' => [
        'visible' => [
          [':input[name="third_party_settings[geocoder_field][method]"]' => ['value' => 'source']],
        ],
      ],
    ];
    $failure = (array) $field->getThirdPartySetting('geocoder_field', 'failure') + [
      'handling' => 'preserve',
      'status_message' => TRUE,
      'log' => TRUE,
    ];
    $element['failure']['handling'] = [
      '#type' => 'radios',
      '#title' => $this->t('What to store if geo-coding fails?'),
      '#description' => $this->t('Is possible that the source field cannot be geo-coded. Choose what to store in this field in such case.'),
      '#options' => [
        'preserve' => $this->t('Preserve the existing field value'),
        'empty' => $this->t('Empty the field value'),
      ],
      '#default_value' => $failure['handling'],
      '#states' => $invisible_state,
    ];
    $element['failure']['status_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show a status message warning in case of geo-coding failure.'),
      '#default_value' => $failure['status_message'],
      '#states' => $invisible_state,
    ];
    $element['failure']['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log the geo-coding failure.'),
      '#default_value' => $failure['log'],
      '#states' => $invisible_state,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(array $form, FormStateInterface &$form_state) {
    $form_values = $form_state->getValues();

    if ($form_values['method'] !== 'none' && empty($form_values['plugins'])) {
      $form_state->setError($form['third_party_settings']['geocoder_field']['plugins'], t('The selected Geocode operation needs at least one plugin.'));
    }

    // On Reverse Geocode the delta_handling should always be 'default'
    // (many to many), because the other scenario is not admittable.
    if ($form_values['method'] == 'destination') {
      $form_state->setValue('delta_handling', 'default');
    }
  }

}
