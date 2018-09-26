<?php

/**
 * @file
 * Alter form functionality Field States UI.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field_states_ui\FieldStateManager;

/**
 * Implements hook_field_widget_form_alter().
 */
function field_states_ui_field_widget_alter(&$element, FormStateInterface $form_state, $context, $multivalue = FALSE) {
  $manager = \Drupal::service('plugin.manager.field_states_ui.fieldstate');
  /** @var \Drupal\Core\Field\PluginSettingsInterface $plugin */
  $plugin = $context['widget'];

  // A copy of the field is displayed on the field configuration form for
  // entering defaults etc, don't want to alter that.
  if (is_a($form_state->getFormObject(), 'Drupal\field_ui\Form\FieldConfigEditForm')) {
    return;
  }

  // Check that it is enabled for this field.
  if (empty($plugin->getThirdPartySettings('field_states_ui')['field_states'])) {
    return;
  }
  $states = $plugin->getThirdPartySettings('field_states_ui')['field_states'];

  if ($multivalue) {
    $parents = isset($element['#field_parents']) ? $element['#field_parents'] : [];
    $element['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $parents);
    return;
  }

  $field_definition = $context['items']->getFieldDefinition();
  $type             = $field_definition->getType();
  $plugin_id        = $plugin->getPluginId();

  // Handle the type of field appropriately.
  switch ($type) {

    case 'entity_reference':
      switch ($plugin_id) {
        case 'chosen_select':
        case 'options_select':
        case 'options_buttons':
        case 'entity_browser_entity_reference':
          $element['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']);
          break;

        case 'entity_reference_autocomplete':
        case 'entity_reference_autocomplete_tags':
          $element['target_id']['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['target_id']['#field_parents']);
          break;

        default:
          // Log a notice so that user(s) can report unrecognized field
          // plugin_id.
          \Drupal::logger('field_states_ui')->notice(
            t(
              'Field type: "@type" with plugin_id "@id" was unrecognized. Please report on the @link. For quickest resolution, please include what module it comes from.',
              [
                '@type' => $type,
                '@id'   => $plugin_id,
                '@link' => Link::fromTextAndUrl(
                  t('Field States UI Issue Queue'),
                  Url::fromUri('https://www.drupal.org/project/issues/field_states_ui')
                )->toString(),
              ]
            )
          );
          $element['target_id']['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['target_id']['#field_parents']);
          break;
      }
      break;

    case 'boolean':
      switch ($plugin_id) {
        case 'options_buttons':
          $element['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']);
          break;

        default:
          $element['value']['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']);
          break;
      }
      break;

    case 'datetime':
    case 'decimal':
    case 'integer':
    case 'string':
    case 'string_long':
      $element['value']['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['value']['#field_parents']);
      break;

    case 'text_with_summary':
    case 'text_long':
    case 'list_float':
    case 'list_integer':
    case 'list_string':
    case 'link':
      switch ($plugin_id) {
        case 'chosen_select':
        case 'options_select':
        case 'options_buttons':
        default:
          $element['#states'] = field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']);
      }
      break;

    case 'name':
      $element = [
        'element' => $element,
        '#type'   => 'container',
        '#states' => field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']),
      ];
      break;

    default:
      // Log a notice so that user(s) can report unrecognized field types.
      \Drupal::logger('field_states_ui')->notice(
        t(
          'Field type: "@type" was unrecognized. Please report on the @link. For quickest resolution, please include what module it comes from.',
          [
            '@type' => $type,
            '@link' => Link::fromTextAndUrl(
              t('Field States UI Issue Queue'),
              Url::fromUri('https://www.drupal.org/project/issues/field_states_ui')
            )->toString(),
          ]
        )
      );

      // Add a container element and set states on that to ensure it works.
      // This increases divitis which is already common on Drupal forms so
      // it is better to know handle the element properly. There are elements
      // that it does make sense to do this to (ie name) but avoid if possible.
      $element = [
        'element' => $element,
        '#type'   => 'container',
        '#states' => field_states_ui_get_states($states, $manager, $form_state, $context, $element, $element['#field_parents']),
      ];
      break;
  }
}

/**
 * Returns the field states for a given element.
 *
 * @param array[] $field_states
 *   An array of field state configuration.
 * @param \Drupal\field_states_ui\FieldStateManager $manager
 *   Manages field state plugins.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Provides an interface for an object containing the current state of a form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - widget: The widget plugin instance.
 *   - items: The field values, as a
 *     \Drupal\Core\Field\FieldItemListInterface object.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *   - default: A boolean indicating whether the form is being shown as a dummy
 *     form to set default values.
 * @param array $element
 *   The field widget form element as constructed by
 *   \Drupal\Core\Field\WidgetBaseInterface::form().
 * @param array $parents
 *   The current element's parents in the form.
 *
 * @return array
 *   An array of states to render.
 */
function field_states_ui_get_states(array $field_states, FieldStateManager $manager, FormStateInterface $form_state, array $context, array $element, array $parents) {
  $states = [];
  foreach ($field_states as $state) {
    if (!isset($state['id'])) {
      continue;
    }
    try {
      /** @var \Drupal\field_states_ui\FieldStateInterface $field_state */
      $field_state = $manager->createInstance($state['id'], $state);
    }
    catch (\Exception $exception) {
      continue;
    }
    $field_state->applyState($states, $form_state, $context, $element, $parents);
  }
  return $states;
}
