<?php

namespace Drupal\geocoder_field\Plugin\Geocoder\Field;

use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\geocoder\DumperPluginManager;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\geocoder_field\GeocoderFieldPluginInterface;
use Drupal\geocoder_field\GeocoderFieldPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *     "string"
 *   }
 * )
 */
class DefaultField extends PluginBase implements GeocoderFieldPluginInterface, ContainerFactoryPluginInterface {
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
   * Constructs a 'default' plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geocoder_field\GeocoderFieldPluginManager $field_plugin_manager
   *   The plugin manager for this type of plugins.
   * @param \Drupal\geocoder\DumperPluginManager $dumper_plugin_manager
   *   The dumper plugin manager service.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeocoderFieldPluginManager $field_plugin_manager, DumperPluginManager $dumper_plugin_manager, ProviderPluginManager $provider_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldPluginManager = $field_plugin_manager;
    $this->dumperPluginManager = $dumper_plugin_manager;
    $this->providerPluginManager = $provider_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geocoder_field.plugin.manager.field'),
      $container->get('plugin.manager.geocoder.dumper'),
      $container->get('plugin.manager.geocoder.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(FieldConfigInterface $field, array $form, FormStateInterface &$form_state) {
    $element = [
      '#type' => 'details',
      '#title' => t('Geocode'),
      '#open' => TRUE,
    ];

    $element['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Geocode method'),
      '#options' => [
        'none' => $this->t('No geocoding'),
        'source' => $this->t('Geocode from an existing field'),
        'destination' => $this->t('Reverse geocode from an existing field'),
      ],
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'method', 'none'),
    ];

    $invisible_state = [
      'invisible' => [
        ':input[name="third_party_settings[geocoder_field][method]"]' => ['value' => 'none'],
      ],
    ];

    $element['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Geocode from an existing field'),
      '#description' => $this->t('Select which field you would like to use.'),
      '#default_value' => $field->getThirdPartySetting('geocoder_field', 'field'),
      '#options' => $this->fieldPluginManager->getSourceFields($field->getTargetEntityTypeId(), $field->getTargetBundle(), $field->getName()),
      '#states' => $invisible_state,
    ];
    $element['plugins'] = [
      '#type' => 'table',
      '#header' => [t('Geocoder plugins'), $this->t('Weight')],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'plugins-order-weight',
      ],
      ],
      '#caption' => $this->t('Select the Geocoder plugins to use, you can reorder them. The first one to return a valid value will be used.'),
      // We need this class for #states to hide the entire table.
      '#attributes' => ['class' => ['js-form-item']],
      '#states' => $invisible_state,
    ];

    $default_plugins = (array) $field->getThirdPartySetting('geocoder_field', 'plugins');
    $plugins = array_combine($default_plugins, $default_plugins);
    foreach ($this->providerPluginManager->getPluginsAsOptions() as $plugin_id => $plugin_name) {
      // Non-default values are appended at the end.
      $plugins[$plugin_id] = $plugin_name;
    }
    foreach ($plugins as $plugin_id => $plugin_name) {
      $element['plugins'][$plugin_id] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => $plugin_name,
          '#default_value' => in_array($plugin_id, $default_plugins),
        ],
        'weight' => array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['plugins-order-weight']],
        ),
        '#attributes' => ['class' => ['draggable']],
      ];
    }

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
      '#states' => $invisible_state,
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
  public function validateSettingsForm(array $form, FormStateInterface &$form_state) {}

}
