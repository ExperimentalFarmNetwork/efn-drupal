<?php

namespace Drupal\geofield\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Provides the Geofield Proximity Source plugin manager.
 */
class GeofieldProximitySourceManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/GeofieldProximitySource', $namespaces, $module_handler, 'Drupal\geofield\Plugin\GeofieldProximitySourceInterface', 'Drupal\geofield\Annotation\GeofieldProximitySource');

    $this->alterInfo('geofield_geofield_proximity_source_info');
    $this->setCacheBackend($cache_backend, 'geofield_geofield_proximity_source_plugins');
  }

  /**
   * Builds the common elements of the Proximity Form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $context
   *   The array list of the specific view handler plugin type to look for.
   *   Possible values:
   *   - filter
   *   - sort
   *   - field
   *   - NULL (all).
   * @param bool $is_exposed
   *   The check/differentiate if it is part of an exposed form.
   */
  public function buildCommonFormElements(array &$form, FormStateInterface $form_state, $context = NULL, $is_exposed = FALSE) {

    // Attach Geofield Map Libraries.
    $form['#attached']['library'][] = 'geofield/geofield_proximity';

    $form['units'] = [
      '#type' => 'select',
      '#title' => t('Unit of Measure'),
      '#description' => '',
      '#options' => geofield_radius_options(),
      '#default_value' => '',
    ];

    $form['source_intro'] = [
      '#markup' => $this->t('How do you want to enter your proximity parameters (distance and origin point)?'),
    ];

    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Proximity Definition Mode (Source of Distance and Origin Point)'),
      '#options' => [],
      '#default_value' => '',
      '#ajax' => [
        'callback' => [get_class($this), 'sourceUpdate'],
        'effect' => 'fade',
      ],
    ];

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if (isset($definition['context'])
        && (empty($definition['context']) || in_array($context, $definition['context']))
        && (!isset($definition['exposedOnly']) || ($definition['exposedOnly'] && $is_exposed))
      ) {
        $form['source']['#options'][$plugin_id] = $definition['label'];
      }
    }

    $form['source_configuration'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#prefix' => '<div id="proximity-source-configuration">',
      '#suffix' => '</div>',
    ];

  }

  /**
   * Ajax callback triggered on Source Selection.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with updated form element.
   */
  public static function sourceUpdate(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(
      '#proximity-source-configuration',
      $form['options']['source_configuration']
    ));
    return $response;
  }

}
