<?php

namespace Drupal\geofield\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\geofield\Plugin\views\GeofieldProximityHandlerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\geofield\Plugin\GeofieldProximitySourceManager;

/**
 * Field handler to sort Geofields by proximity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsSort("geofield_proximity_sort")
 */
class GeofieldProximitySort extends SortPluginBase {

  use GeofieldProximityHandlerTrait;

  /**
   * The geofield proximity manager.
   *
   * @var \Drupal\geofield\Plugin\GeofieldProximitySourceManager
   */
  protected $proximitySourceManager;

  /**
   * The Geofield Proximity Source Plugin.
   *
   * @var \Drupal\geofield\Plugin\GeofieldProximitySourceInterface
   */
  protected $sourcePlugin;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['units'] = ['default' => 'GEOFIELD_KILOMETERS'];

    // Data sources and info needed.
    $options['source'] = ['default' => 'geofield_manual_origin'];
    $options['source_configuration'] = ['default' => []];

    return $options;
  }

  /**
   * Constructs the GeofieldProximitySort object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geofield\Plugin\GeofieldProximitySourceManager $proximity_source_manager
   *   The Geofield Proximity Source manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GeofieldProximitySourceManager $proximity_source_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->proximitySourceManager = $proximity_source_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geofield_proximity_source')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->addQueryOrderBy($this->options['order']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    $context = $this->pluginDefinition['plugin_type'];

    $user_input = $form_state->getUserInput();
    $source_plugin_id = isset($user_input['options']['source']) ? $user_input['options']['source'] : $this->options['source'];
    $source_plugin_configuration = isset($user_input['options']['source_configuration']) ? $user_input['options']['source_configuration'] : $this->options['source_configuration'];

    $this->proximitySourceManager->buildCommonFormElements($form, $form_state, $context);

    $form['units']['#default_value'] = $this->options['units'];
    $form['source']['#default_value'] = $this->options['source'];

    try {
      $this->sourcePlugin = $this->proximitySourceManager->createInstance($source_plugin_id, $source_plugin_configuration);
      $this->sourcePlugin->setViewHandler($this);
      $form['source_configuration']['origin_description'] = [
        '#markup' => $this->sourcePlugin->getPluginDefinition()['description'],
        '#weight' => -10,
      ];
      $this->sourcePlugin->buildOptionsForm($form['source_configuration'], $form_state, ['source_configuration']);
    }
    catch (\Exception $e) {
      watchdog_exception('geofield', $e);
    }

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    try {
      $this->sourcePlugin->validateOptionsForm($form['source_configuration'], $form_state, ['source_configuration']);
    }
    catch (\Exception $e) {
      watchdog_exception('geofield', $e);
      $form_state->setErrorByName($form['source'], t("The Proximity Source couldn't be set due to: @error", [
        '@error' => $e,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $output = parent::adminSummary();
    return $this->options['source'] . ' - ' . $output;
  }

}
