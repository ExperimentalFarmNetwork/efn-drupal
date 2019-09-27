<?php

namespace Drupal\geocoder\Form;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\Core\Url;
use Drupal\Core\Render\RendererInterface;

/**
 * The geocoder settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The typed config service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The Link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * The provider plugin manager service.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $providerPluginManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\geocoder\ProviderPluginManager $provider_plugin_manager
   *   The provider plugin manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    LinkGeneratorInterface $link_generator,
    RendererInterface $renderer,
    LanguageManagerInterface $language_manager,
    ProviderPluginManager $provider_plugin_manager
  ) {
    parent::__construct($config_factory);
    $this->typedConfigManager = $typedConfigManager;
    $this->link = $link_generator;
    $this->renderer = $renderer;
    $this->languageManager = $language_manager;
    $this->providerPluginManager = $provider_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('link_generator'),
      $container->get('renderer'),
      $container->get('language_manager'),
      $container->get('plugin.manager.geocoder.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geocoder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['geocoder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('geocoder.settings');

    $geocoder_config_schema = $this->typedConfigManager->getDefinition('geocoder.settings') + ['mapping' => []];
    $geocoder_config_schema = $geocoder_config_schema['mapping'];

    // Attach Geofield Map Library.
    $form['#attached']['library'] = [
      'geocoder/general',
    ];

    $form['geocoder_presave_disabled'] = [
      '#type' => 'checkbox',
      '#title' => $geocoder_config_schema['geocoder_presave_disabled']['label'],
      '#description' => $geocoder_config_schema['geocoder_presave_disabled']['description'],
      '#default_value' => $config->get('geocoder_presave_disabled'),
    ];

    $form['cache'] = [
      '#type' => 'checkbox',
      '#title' => $geocoder_config_schema['cache']['label'],
      '#description' => $geocoder_config_schema['cache']['description'],
      '#default_value' => $config->get('cache'),
    ];

    $geocoder_php_library_link = $this->link->generate(t('Geocoder Php Library'), Url::fromUri('http://geocoder-php.org/Geocoder/#address-based-providers', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));

    $form['geocoder_plugins_title'] = [
      '#type' => 'item',
      '#title' => t('Geocoder plugin(s) Options'),
      '#description' => t('Set the Options to be used on your plugins. As a good help it is possible to refer to the requirements listed in the @geocoder_php_library_link documentation.', [
        '@geocoder_php_library_link' => $geocoder_php_library_link,
      ]),
    ];

    $form['plugins'] = [
      '#type' => 'table',
      '#weight' => 20,
      '#header' => [
        $this->t('Geocoder plugins'),
        $this->t('Options / Arguments'),
      ],
      '#attributes' => [
        'class' => [
          'geocode-plugins-list',
        ],
      ],
    ];

    $rows = [];
    foreach ($this->providerPluginManager->getPlugins() as $plugin) {
      $plugin_config_schema = [];

      if ($this->typedConfigManager->hasConfigSchema('geocoder.settings.plugins.' . $plugin['id'])) {
        $plugin_config_schema = $this->typedConfigManager->getDefinition('geocoder.settings.plugins.' . $plugin['id']);
        $plugin_config_schema = isset($plugin_config_schema['mapping']) ? $plugin_config_schema['mapping'] : [];
      }

      $rows[$plugin['id']] = [
        'name' => [
          '#plain_text' => $plugin['name'],
        ],
      ];

      foreach ($plugin_config_schema as $argument => $argument_type) {
        $plugin_config_schema[$argument] += [
          'label' => $plugin['id'],
          'description' => NULL,
        ];

        $plugin['arguments'] += [$argument => $plugin['arguments'][$argument]];

        $plugin_config_schema += [
          $argument => [
            'label' => $argument,
            'description' => NULL,
          ],
        ];

        switch ($argument_type['type']) {
          case 'boolean':
            $type = 'checkbox';
            break;

          case 'string':
          case 'color_hex':
          case 'path':
          case 'label':
            $type = 'textfield';
            break;

          case 'text':
            $type = 'textarea';
            break;

          case 'integer':
            $type = 'number';
            break;

          default:
            $type = 'textfield';
        }

        $rows[$plugin['id']]['options'][$argument] = [
          '#type' => $type,
          '#title' => $plugin_config_schema[$argument]['label'],
          '#description' => $plugin_config_schema[$argument]['description'],
          '#default_value' => $plugin['arguments'][$argument],
        ];
      }

      if (empty($rows[$plugin['id']]['options'])) {
        $rows[$plugin['id']]['options'] = [
          '#type' => 'value',
          '#value' => [],
          'notes' => [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this->t("This plugin doesn't accept arguments."),
            '#attributes' => [
              'class' => [
                'options-notes',
              ],
            ],
          ],
        ];
      }
    }

    foreach ($rows as $plugin_id => $row) {
      $form['plugins'][$plugin_id] = $row;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get all the form state values, in an array structure.
    $form_state_values = $form_state->getValues();

    $plugins_options = [];
    foreach ($form_state_values['plugins'] as $k => $plugin) {
      $plugins_options[$k] = $form_state_values['plugins'][$k]['options'];
    }

    $config = $this->config('geocoder.settings');
    $config->set('geocoder_presave_disabled', $form_state_values['geocoder_presave_disabled']);
    $config->set('cache', $form_state_values['cache']);
    $config->set('plugins_options', $plugins_options);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
