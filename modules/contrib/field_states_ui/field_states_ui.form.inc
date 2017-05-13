<?php

/**
 * @file
 * Alter form functionality Field States UI.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Implements hook_field_widget_form_alter().
 */
function field_states_ui_field_widget_alter(&$element, FormStateInterface $form_state, $context) {
  $field_state_manager = Drupal::service('plugin.manager.field_states_ui.fieldstate');
  $plugin = $context['widget'];

  // A copy of the field is displayed on the field configuration form for
  // entering defaults etc, don't want to alter that.
  if (is_a($form_state->getFormObject(), 'Drupal\field_ui\Form\FieldConfigEditForm')) {
    return;
  }

  // Check that it is enabled for this field.
  if (!$field_states = $plugin->getThirdPartySettings('field_states_ui')) {
    return;
  }

  //Ensure that there are field_states defined.
  if (empty($field_states['field_states'])) {
    return;
  }

  $states = [];
  foreach ($field_states['field_states'] as $state) {
    if (!isset($state['id'])) {
      continue;
    }
    $field_state = $field_state_manager->createInstance($state['id'], $state);
    $field_state->applyState($states, $form_state, $context, $element);
  }
  if ($states) {
    if (isset($context['multiple'])) {
      $element['#type'] = 'container';
      $element['#states'] = $states;
      return;
    }
    $field_definition = $context['items']->getFieldDefinition();
    $type = $field_definition->getType();
    $plugin_id = $plugin->getPluginId();
    if ($type === 'entity_reference') {
      if ($plugin_id === 'options_select' || $plugin_id === 'options_buttons') {
        $element['#states'] = $states;
      }
      elseif ($plugin_id === 'entity_reference_autocomplete' || $plugin_id === 'entity_reference_autocomplete_tags') {
        $element['target_id']['#states'] = $states;
      }
    }
    elseif ($type === 'datetime' || $type === 'decimal' || $type === 'string_long' || $type === 'boolean') {
      $element['value']['#states'] = $states;
    }
    elseif ($type === 'text_with_summary') {
      $element['#states'] = $states;
    }
    elseif ($type === 'name') {
      $element = [
        'element' => $element,
        '#type' => 'container',
        '#states' => $states,
      ];
    }
    else {
      // Log a notice so that user(s) can report unrecognized field types.
      \Drupal::logger('field_states_ui')->notice(t('Field type: "@type" was unrecognized. Please report on the @link. For quickest resolution, please include what module it comes from.', [
        '@type' => $type,
        '@link' => Link::fromTextAndUrl(t('Field States UI Issue Queue'),
          Url::fromUri('https://www.drupal.org/project/issues/field_states_ui')
        )->toString(),
      ]));

      // Add a container element and set states on that to ensure it works.
      // This increases divitis which is already common on Drupal forms so
      // it is better to know handle the element properly. There are elements
      // that it does make sense to do this to (ie name) but avoid if possible.
      $element = [
        'element' => $element,
        '#type' => 'container',
        '#states' => $states,
      ];
    }
  }
}
