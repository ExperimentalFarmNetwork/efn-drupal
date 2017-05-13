<?php

/**
 * @file
 * Admin UI functionality Field States UI.
 */

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_field_widget_third_party_settings_form().
 */
function field_states_ui_settings_form(WidgetInterface $plugin, FieldDefinitionInterface $field_definition, $form_mode, $form, FormStateInterface $form_state) {
  $current = $plugin->getThirdPartySetting('field_states_ui', 'states', []);
  $field_state_manager = Drupal::service('plugin.manager.field_states_ui.fieldstate');
  $field_name = $field_definition->getName();
  $element = [
    'form' => [
      '#type' => 'fieldset',
      '#title' => t('Manage Field States'),
      '#description' => t('Configure field states - ie automatic hiding/showing of fields.'),
      'list' => [
        '#type' => 'table',
        '#header' => [
          t('Type'),
          t('Comparison'),
          t('Operations'),
        ],
        '#empty' => t('There are no field states applied to this field currently. Add one by selecting an option below.'),
      ],
    ],
  ];

  $cancel = [
    '#type' => 'submit',
    '#value' => t('Cancel'),
    '#submit' => ['field_states_ui_submit'],
    '#field_name' => $field_name,
    '#limit_validation_errors' => [],
    '#op' => 'cancel',
  ];

  // Display and maintain existing form states with edit options.
  $field_states_settings = $plugin->getThirdPartySettings('field_states_ui');
  if (!empty($field_states_settings['field_states'])) {
    $element['field_states'] = [];
    foreach ($field_states_settings['field_states'] as $key => $state) {
      if (!isset($state['id'])) {
        continue;
      }
      $field_state = $field_state_manager->createInstance($state['id'], $state);
      $element['form']['list'][$key] = [
        'type' => [
          '#type' => 'markup',
          '#markup' => $field_state->label(),
        ],
        'comparison' => $field_state->getSummary(),
        'operations' => [
          'edit' => [
            '#type' => 'submit',
            '#value' => t('Edit'),
            '#op' => 'edit',
            '#submit' => ['field_states_ui_submit'],
            '#field_name' => $field_name,
            '#key' => $key,
            '#name' => 'edit-'.$key,
          ],
          'delete' => [
            '#type' => 'submit',
            '#value' => t('Delete'),
            '#op' => 'delete',
            '#submit' => ['field_states_ui_submit'],
            '#field_name' => $field_name,
            '#key' => $key,
            '#name' => 'delete-'.$key,
          ],
        ],
      ];
      $element['field_states'][$field_state->getUuid()] = [
        'uuid' => [
          '#type' => 'hidden',
          '#value' => $field_state->getUuid(),
        ],
        'id' => [
          '#type' => 'hidden',
          '#value' => $field_state->getPluginId(),
        ],
        'data' => $field_state->getConfigurationForForm(),
      ];
    }
  }

  // Provide form elements to edit/add form states.
  if ($form_state->get('field_states_ui_edit') == $field_name) {
    if ($form_state->get('field_states_ui_target')) {
      $target = $form_state->get('field_states_ui_target');
      $states = $plugin->getThirdPartySettings('field_states_ui')['field_states'];
      if (!isset($states[$target])) {
        return $element;
      }
      $type = $states[$target]['id'];
      $field_state = $field_state_manager->createInstance($type, $states[$target]);
      $title = t('Edit field state: @type', ['@type' => $field_state->label()]);
      $submit_label = t('Update State');
      $op = 'process_update';
    }
    else {
      $type = $form_state->getValue([
        'fields',
        $field_name,
        'settings_edit_form',
        'third_party_settings',
        'field_states_ui',
        'form',
        'type',
      ]);
      $field_state = $field_state_manager->createInstance($type);
      $title = t('Add new field state: @type', ['@type' => $field_state->label()]);
      $submit_label = t('Add');
      $op = 'new';
    }
    $element['form']['edit'] = $field_state->buildConfigurationForm([], $form_state);
    $element['form']['edit']['#type'] = 'fieldset';
    $element['form']['edit']['#title'] = $title;
    $element['form']['edit']['submit'] = [
      'save' => [
        '#type' => 'submit',
        '#value' => $submit_label,
        '#validate' => ['field_states_ui_validate'],
        '#submit' => ['field_states_ui_submit'],
        '#field_name' => $field_name,
        '#op' => $op,
        '#plugin' => $type,
      ],
      'cancel' => $cancel,
    ];
  }

  // Provide form elements to confirm delete action.
  elseif ($form_state->get('field_states_ui_edit') == 'delete') {
    $element['form']['delete'] = [
      '#type' => 'fieldset',
      '#tree' => FALSE,
      '#title' => t('Delete field state?'),
      'delete_submit' => [
        '#type' => 'submit',
        '#value' => t('Confirm Delete'),
        '#submit' => ['field_states_ui_submit'],
        '#target' => $form_state->get('field_states_ui_target'),
        '#op' => 'process_delete',
        '#field_name' => $field_name,
      ],
      'cancel' => $cancel,
    ];
  }

  //Provide form elements to select a new form state type.
  else {
    $field_state_options = [];
    $field_states = $field_state_manager->getDefinitions();
    foreach ($field_states as $field_state => $definition) {
      $field_state_options[$field_state] = $definition['label'];
    }
    $element['form']['type'] = [
      '#type' => 'select',
      '#title' => t('Field States'),
      '#title_display' => 'invisible',
      '#options' => $field_state_options,
      '#empty_option' => t('Select a new field state'),
    ];
    $element['form']['add'] = [
      '#type' => 'submit',
      '#value' => t('Add'),
      '#validate' => ['field_states_ui_validate'],
      '#submit' => ['field_states_ui_submit'],
      '#field_name' => $field_name,
      '#op' => 'add',
    ];
  }
  return $element;
}

/**
 * Submit function to add/edit field states.
 */
function field_states_ui_submit($form, FormStateInterface $form_state) {
  $trigger = $form_state->getTriggeringElement();
  $field_name = $trigger['#field_name'];
  $form_state->set('plugin_settings_edit', $field_name);

  // Show the add new field state form for the specified field state type.
  if ($trigger['#op'] == 'add') {
    $form_state->set('field_states_ui_edit', $field_name);
  }

  // Cancel editing/creating/deleting a field state.
  elseif ($trigger['#op'] == 'cancel') {
    $form_state->set('field_states_ui_edit', NULL);
    $form_state->set('field_states_ui_target', NULL);
  }

  // Show the edit field state form for the selected field state.
  elseif ($trigger['#op'] == 'edit') {
    $form_state->set('field_states_ui_edit', $field_name);
    $form_state->set('field_states_ui_target', $trigger['#key']);
  }

  // Show confirm dialogue for form state deletion.
  elseif ($trigger['#op'] == 'delete') {
    $form_state->set('field_states_ui_edit', 'delete');
    $form_state->set('field_states_ui_target', $trigger['#key']);
  }

  // Process deleting a field state.
  elseif ($trigger['#op'] == 'process_delete') {
    $entity = $form_state->getFormObject()->getEntity();
    $field = $entity->getComponent($field_name);
    unset($field['third_party_settings']['field_states_ui']['field_states'][$form_state->get('field_states_ui_target')]);
    $entity->setComponent($field_name, $field);
    $entity->save();
    $form_state->set('field_states_ui_edit', NULL);
    $form_state->set('field_states_ui_target', NULL);
  }

  // Add a new field state and save the field/entity.
  elseif ($trigger['#op'] == 'new') {
    $entity = $form_state->getFormObject()->getEntity();
    $field = $entity->getComponent($field_name);

    $field_state_manager = Drupal::service('plugin.manager.field_states_ui.fieldstate');
    $field_state = $field_state_manager->createInstance($trigger['#plugin']);
    $field_state_data = $form_state->getValue([
      'fields',
      $field_name,
      'settings_edit_form',
      'third_party_settings',
      'field_states_ui',
      'form',
      'edit',
    ]);
    $field_state->submitConfigurationForm($form, (new FormState())->setValues($field_state_data));
    $field['third_party_settings']['field_states_ui']['field_states'][$field_state->getUuid()] = $field_state->getConfiguration();
    $entity->setComponent($field_name, $field);
    $entity->save();
    $form_state->set('field_states_ui_edit', NULL);
  }

  // Update a field state and save the field/entity.
  elseif ($trigger['#op'] == 'process_update') {
    $entity = $form_state->getFormObject()->getEntity();
    $field = $entity->getComponent($field_name);
    $field_state_manager = Drupal::service('plugin.manager.field_states_ui.fieldstate');
    $target = $form_state->get('field_states_ui_target');
    $field_state = $field_state_manager->createInstance($trigger['#plugin'], $field['third_party_settings']['field_states_ui']['field_states'][$target]);
    $field_state_data = $form_state->getValue([
      'fields',
      $field_name,
      'settings_edit_form',
      'third_party_settings',
      'field_states_ui',
      'form',
      'edit',
    ]);
    $field_state->submitConfigurationForm($form, (new FormState())->setValues($field_state_data));
    $field['third_party_settings']['field_states_ui']['field_states'][$field_state->getUuid()] = $field_state->getConfiguration();
    $entity->setComponent($field_name, $field);
    $entity->save();
    $form_state->set('field_states_ui_edit', NULL);
    $form_state->set('field_states_ui_target', NULL);
  }

  $form_state->setRebuild();
}

/**
 * Validation function for adding/editing field states.
 */
function field_states_ui_validate($form, FormStateInterface $form_state) {
  $trigger = $form_state->getTriggeringElement();
  $field_name = $trigger['#field_name'];
  $op = $trigger['#op'];

  if ($op == 'add') {
    $element = "fields][$field_name][settings_edit_form][third_party_settings][field_states_ui][form][type";
    $type = $form_state->getValue([
      'fields',
      $field_name,
      'settings_edit_form',
      'third_party_settings',
      'field_states_ui',
      'form',
      'type',
    ]);
    if (!$type) {
      $form_state->setErrorByName($element, t('You must select a field state to add.'));
    }
  }
}
