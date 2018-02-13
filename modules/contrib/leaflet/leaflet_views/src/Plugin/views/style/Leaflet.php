<?php

namespace Drupal\leaflet_views\Plugin\views\style;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Leaflet\LeafletService;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "leaflet",
 *   title = @Translation("Leaflet"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map",
 *   no_ui = TRUE
 * )
 */
class Leaflet extends StylePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Entity Field manager service property.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Display Repository service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * Constructs a LeafletMap style instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display
   *   The entity display manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityDisplayRepositoryInterface $entity_display,
    RendererInterface $renderer,
    LeafletService $leaflet_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityDisplay = $entity_display;
    $this->renderer = $renderer;
    $this->leafletService = $leaflet_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Choose a map preset.
    $map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = $map['label'];
    }
    $form['map'] = [
      '#title' => $this->t('Map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $this->options['map'] ?: '',
      '#required' => TRUE,
    ];

    $form['height'] = [
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => $this->t('px'),
      '#size' => 4,
      '#default_value' => $this->options['height'],
      '#required' => TRUE,
    ];

    // @todo add note about adding leaflet attachments for data points.
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $height = $form_state->getValue(['style_options', 'height']);
    if (!empty($style_options['height']) && (!is_numeric($height) || $height <= 0)) {
      $form_state->setError($form['height'], $this->t('Map height needs to be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evenEmpty() {
    // Render map even if there is no data.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Avoid querying the database; all feature data comes from attachments.
    $this->built = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $features = [];
    foreach ($this->view->attachment_before as $id => $attachment) {
      if (!empty($attachment['#leaflet-attachment'])) {
        $features = array_merge($features, $attachment['rows']);
        $this->view->element['#attached'] = NestedArray::mergeDeep($this->view->element['#attached'], $attachment['#attached']);
        unset($this->view->attachment_before[$id]);
      }
    }

    $map_info = leaflet_map_get_info($this->options['map']);
    // Enable layer control by default, if we have more than one feature group.
    if (self::hasFeatureGroups($features)) {
      $map_info['settings'] += ['layerControl' => TRUE];
    }
    $element = $this->leafletService->leafletRenderMap($map_info, $features, $this->options['height'] . 'px');

    // Merge #attached libraries.
    $this->view->element['#attached'] = NestedArray::mergeDeep($this->view->element['#attached'], $element['#attached']);
    $element['#attached'] =& $this->view->element['#attached'];

    return $element;
  }

  /**
   * Checks whether the given array of features contains any groups.
   *
   * @param array $features
   *   The features.
   *
   * @return bool
   *   The result.
   */
  protected static function hasFeatureGroups(array $features) {
    foreach ($features as $feature) {
      if (!empty($feature['group'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if (empty($this->options['map'])) {
      $errors[] = $this->t('Style @style requires a leaflet map to be configured.', ['@style' => $this->definition['title']]);
    }
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['map'] = ['default' => ''];
    $options['height'] = ['default' => '400'];
    return $options;
  }

}
