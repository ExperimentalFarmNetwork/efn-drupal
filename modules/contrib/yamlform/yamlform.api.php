<?php

/**
 * @file
 * Hooks related to YAML Form module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\yamlform\Annotation\YamlFormElement.
 *
 * @param array $elements
 *   The array of form handlers, keyed on the machine-readable element name.
 */
function hook_yamlform_element_info_alter(array &$elements) {

}

/**
 * Alter the information provided in \Drupal\yamlform\Annotation\YamlFormHandler.
 *
 * @param array $handlers
 *   The array of form handlers, keyed on the machine-readable handler name.
 */
function hook_yamlform_handler_info_alter(array &$handlers) {

}

/**
 * Alter form elements.
 *
 * @param array $element
 *   The form element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which elements is being attached.
 *
 * @see \Drupal\yamlform\YamlFormSubmissionForm::prepareElements()
 * @see hook_yamlform_element_ELEMENT_TYPE_form_alter()
 */
function hook_yamlform_element_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Code here acts on all elements included in a form.
  /** @var \Drupal\yamlform\YamlFormSubmissionForm $form_object */
  $form_object = $form_state->getFormObject();
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $form_object->getEntity();
  /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
  $yamlform = $yamlform_submission->getYamlForm();

  // Add custom data attributes to all elements.
  $element['#attributes']['data-custom'] = '{custom data goes here}';
}

/**
 * Alter form elements for a specific type.
 *
 * Modules can implement hook_yamlform_element_ELEMENT_TYPE_form_alter() to
 * modify a specific form element, rather than using
 * hook_yamlform_element_alter() and checking the element type.
 *
 * @param array $element
 *   The form element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array. See hook_field_widget_form_alter() for the structure
 *   and content of the array.
 *
 * @see \Drupal\yamlform\YamlFormSubmissionForm::prepareElements()
 * @see hook_yamlform_element_alter(()
 */
function hook_yamlform_element_ELEMENT_TYPE_form_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Add custom data attributes to a specific element type.
  $element['#attributes']['data-custom'] = '{custom data goes here}';

  // Attach a custom library to the element type.
  $element['#attached']['library'][] = 'MODULE/MODULE.element.ELEMENT_TYPE';
}

/**
 * Alter the form options by id.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The form element that the options is for.
 */
function hook_yamlform_options_YAMLFORM_OPTIONS_ID_alter(array &$options, array &$element = []) {

}

/**
 * Perform alterations before a form submission form is rendered.
 *
 * This hook is identical to hook_form_alter() but allows the
 * hook_yamlform_submission_form_alter() function to be stored in a dedicated
 * include file and it also allows the YAML Form module to implement form alter
 * logic on another module's behalf.
 *
 * @param array $form
 *   Nested array of form elements that comprise the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param string $form_id
 *   String representing the form's id.
 *
 * @see yamlform.honeypot.inc
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 *
 * @ingroup form_api
 */
function hook_yamlform_submission_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

}

/**
 * @} End of "addtogroup hooks".
 */
