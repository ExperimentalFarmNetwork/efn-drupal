<?php

namespace Drupal\field_states_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for field states.
 *
 * @see \Drupal\field_states_ui\Annotation\FieldState
 * @see \Drupal\field_states_ui\FieldStateBase
 * @see \Drupal\field_states_ui\FieldStateManager
 * @see plugin_api
 */
interface FieldStateInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Applies a field state to the field widget's form element.
   *
   * @param array $states
   *   An array to hold states.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $context
   *   An associative array containing the following key-value pairs:
   *   - form: The form structure to which widgets are being attached. This may
   *     be a full form structure, or a sub-element of a larger form.
   *   - widget: The widget plugin instance.
   *   - items: The field values, as a
   *     \Drupal\Core\Field\FieldItemListInterface object.
   *   - delta: The order of this item in the array of sub-elements (0, 1, n).
   *   - default: A boolean indicating whether the form is being shown as a
   *     dummy form to set default values.
   * @param array $element
   *   The field widget form element as constructed by hook_field_widget_form().
   *
   * @see \Drupal\Core\Field\WidgetBase::formSingleElement()
   * @see hook_field_widget_form_alter()
   *
   * @return bool
   *   TRUE on success. FALSE if unable to calculate the field state.
   */
  public function applyState(array &$states, FormStateInterface $form_state, array $context, array $element);

  /**
   * Returns a render array summarizing the configuration of the image effect.
   *
   * @return array
   *   A render array.
   */
  public function getSummary();

  /**
   * Returns the field state label.
   *
   * @return string
   *   The field state label.
   */
  public function label();

  /**
   * Returns the unique ID representing the field state.
   *
   * @return string
   *   The field state ID.
   */
  public function getUuid();

}
