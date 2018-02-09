<?php

namespace Drupal\leaflet\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Leaflet\LeafletService;

/**
 * Plugin implementation of the 'leaflet_default' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_formatter_default",
 *   label = @Translation("Leaflet Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class LeafletDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * LeafletDefaultFormatter constructor.
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
   *   Any third party settings settings.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LeafletService $leaflet_service
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->leafletService = $leaflet_service;
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
      $container->get('leaflet.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'leaflet_map' => 'OSM Mapnik',
      'height' => 400,
      'zoom' => 10,
      'minPossibleZoom' => 0,
      'maxPossibleZoom' => 18,
      'minZoom' => 0,
      'maxZoom' => 18,
      'popup' => FALSE,
      'icon' => [
        'icon_url' => '',
        'shadow_url' => '',
        'icon_size' => ['x' => 0, 'y' => 0],
        'icon_anchor' => ['x' => 0, 'y' => 0],
        'shadow_anchor' => ['x' => 0, 'y' => 0],
        'popup_anchor' => ['x' => 0, 'y' => 0],
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $leaflet_map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $leaflet_map_options[$key] = $map['label'];
    }
    $elements['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $leaflet_map_options,
      '#default_value' => $this->getSetting('leaflet_map'),
      '#required' => TRUE,
    ];
    $zoom_options = [];
    for ($i = $this->getSetting('minPossibleZoom'); $i <= $this->getSetting('maxPossibleZoom'); $i++) {
      $zoom_options[$i] = $i;
    }
    $elements['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('zoom'),
      '#required' => TRUE,
    ];
    $elements['minZoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('minZoom'),
      '#required' => TRUE,
    ];
    $elements['maxZoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'select',
      '#options' => $zoom_options,
      '#default_value' => $this->getSetting('maxZoom'),
      '#required' => TRUE,
    ];
    $elements['height'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('height'),
      '#field_suffix' => $this->t('px'),
    ];
    $elements['popup'] = [
      '#title' => $this->t('Popup'),
      '#description' => $this->t('Show a popup for single location fields.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('popup'),
    ];
    $icon = $this->getSetting('icon');
    $elements['icon'] = [
      '#title' => $this->t('Map Icon'),
      '#description' => $this->t('These settings will overwrite the icon settings defined in the map definition.'),
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => empty($icon),
    ];
    $elements['icon']['icon_url'] = [
      '#title' => $this->t('Icon URL'),
      '#description' => $this->t('Can be an absolute or relative URL.'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $icon['icon_url'],
      '#element_validate' => [[$this, 'validateUrl']],
    ];
    $elements['icon']['shadow_url'] = [
      '#title' => $this->t('Icon Shadow URL'),
      '#type' => 'textfield',
      '#maxlength' => 999,
      '#default_value' => $icon['shadow_url'],
      '#element_validate' => [[$this, 'validateUrl']],
    ];

    $elements['icon']['icon_size'] = [
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('Size of the icon image in pixels.'),
    ];
    $elements['icon']['icon_size']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => $icon['icon_size']['x'],
    ];
    $elements['icon']['icon_size']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => $icon['icon_size']['y'],
    ];
    $elements['icon']['icon_anchor'] = [
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The coordinates of the "tip" of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.'),
    ];
    $elements['icon']['icon_anchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => $icon['icon_anchor']['x'],
    ];
    $elements['icon']['icon_anchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => $icon['icon_anchor']['y'],
    ];
    $elements['icon']['shadow_anchor'] = [
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the shadow is shown.'),
    ];
    $elements['icon']['shadow_anchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => $icon['shadow_anchor']['x'],
    ];
    $elements['icon']['shadow_anchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => $icon['shadow_anchor']['y'],
    ];
    $elements['icon']['popup_anchor'] = [
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t('The point from which the marker popup opens, relative to the anchor point.'),
    ];
    $elements['icon']['popup_anchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => $icon['popup_anchor']['x'],
    ];
    $elements['icon']['popup_anchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => $icon['popup_anchor']['y'],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Leaflet Map: @map', ['@map' => $this->getSetting('leaflet_map')]);
    $summary[] = $this->t('Map height: @height px', ['@height' => $this->getSetting('height')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $icon_url = $settings['icon']['icon_url'];

    $map = leaflet_map_get_info($settings['leaflet_map']);
    $map['settings']['zoom'] = isset($settings['zoom']) ? $settings['zoom'] : NULL;
    $map['settings']['minZoom'] = isset($settings['minZoom']) ? $settings['minZoom'] : NULL;
    $map['settings']['maxZoom'] = isset($settings['zoom']) ? $settings['maxZoom'] : NULL;

    $elements = [];
    foreach ($items as $delta => $item) {

      $features = $this->leafletService->leafletProcessGeofield($item->value);

      // If only a single feature, set the popup content to the entity title.
      if ($settings['popup'] && count($items) == 1) {
        $features[0]['popup'] = $items->getEntity()->label();
      }
      if (!empty($icon_url)) {
        foreach ($features as $key => $feature) {
          $features[$key]['icon'] = $settings['icon'];
        }
      }
      $elements[$delta] = $this->leafletService->leafletRenderMap($map, $features, $settings['height'] . 'px');
    }
    return $elements;
  }

  /**
   * Validate Url method.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public function validateUrl(array $element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !UrlHelper::isValid($element['#value'])) {
      $form_state->setError($element, $this->t("Icon Url is not valid."));
    }
  }

}
