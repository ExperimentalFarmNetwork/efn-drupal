<?php

namespace Drupal\field_states_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for field staes.
 *
 * @see \Drupal\field_states_ui\Annotation\FieldState
 * @see \Drupal\field_states_ui\FieldStateInterface
 * @see \Drupal\field_states_ui\FieldStateManager
 * @see plugin_api
 */
abstract class FieldStateBase extends PluginBase implements FieldStateInterface, ContainerFactoryPluginInterface {

  /**
   * The field state ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyState(array &$states, FormStateInterface $form_state, array $context, array $element) {
    $target_field = $form_state->getFormObject()
      ->getFormDisplay($form_state)
      ->getComponent($this->configuration['target']);

    // If dealing with a field on an Inline Entity Form or a Field Collection
    // have to include the field parents in the selector.
    if (!empty($element['#field_parents'])) {
      $target = array_shift($element['#field_parents']) . '[' . implode('][', $element['#field_parents']) . '][' . $this->configuration['target'] . ']';
    }
    else {
      $target = $this->configuration['target'];
    }
    switch ($target_field['type']) {
      case 'options_select':
        $selector = "select[name^='{$target}']";
        break;

      default:
        $selector = ":input[name^='{$target}']";
        break;
    }

    $states[$this->pluginDefinition['id']] = [
      $selector => [
        $this->configuration['comparison'] => $this->configuration['value'],
      ],
    ];

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'field_states_ui_summary',
      '#data' => $this->configuration,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += [
      'data' => [],
      'uuid' => '',
    ];
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    if (!$this->configuration['value']) {
      $this->configuration['value'] = TRUE;
    }
    $this->uuid = $configuration['uuid'] ? $configuration['uuid'] : \Drupal::service('uuid')->generate();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationForForm() {
    $form = [];
    foreach ($this->configuration as $key => $value) {
      $form[$key] = [
        '#type' => 'hidden',
        '#value' => $value,
      ];
    }
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'value' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'data' => $form_state->getValues(),
      'uuid' => $this->uuid,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $display = $form_state->getFormObject()->getEntity();
    $fields = [];
    $definitions = \Drupal::entityManager()->getFieldDefinitions($display->getTargetEntityTypeId(), $display->getTargetBundle());
    $current_field = $form_state->get('field_states_ui_edit');
    foreach ($display->getComponents() as $name => $field) {
      if (!isset($definitions[$name]) || $name === $current_field) {
        continue;
      }
      $fields[$name] = $definitions[$name]->getLabel();
    }
    asort($fields, SORT_NATURAL | SORT_FLAG_CASE);

    $form['target'] = [
      '#type' => 'select',
      '#title' => t('Target'),
      '#description' => t('The field to run a comparison on'),
      '#required' => TRUE,
      '#other' => t('Other element on the page'),
      '#other_description' => t('Should be a valid jQuery style element selector.'),
      '#options' => $fields,
      '#default_value' => isset($this->configuration['target']) ? $this->configuration['target'] : '',
    ];
    $form['comparison'] = [
      '#type' => 'select',
      '#title' => t('Comparison Type'),
      '#options' => [
        'empty' => 'empty',
        'filled' => 'filled',
        'checked' => 'checked',
        'unchecked' => 'unchecked',
        'expanded' => 'expanded',
        'collapsed' => 'collapsed',
        'value' => 'value',
      ],
      '#default_value' => isset($this->configuration['comparison']) ? $this->configuration['comparison'] : '',
    ];
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#states' => [
        'visible' => [
          ':input[name$="[comparison]"]' => ['value' => 'value'],
        ],
      ],
    ];
    return $form;
  }

}
