<?php

namespace Drupal\select_or_other\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'select_or_other_list' widget.
 *
 * @FieldWidget(
 *   id = "select_or_other_list",
 *   label = @Translation("Select or Other"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ListWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getOptions(FieldableEntityInterface $entity = NULL) {
    $options = [];

    if ($entity) {
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider($this->getColumn(), $entity)
        ->getSettableOptions(\Drupal::currentUser());
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element = $element + [
        '#merged_values' => TRUE,
      ];

    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    unset($values['select']);
    unset($values['other']);

    $new_values = $this->extractNewValues($values);

    if (!empty($new_values)) {
      $this->addNewValuesToAllowedValues($new_values);
    }

    return parent::massageFormValues($values, $form, $form_state);
  }

  /**
   * Extract unknown values found in the values array.
   *
   * @param array $values
   *   The values.
   *
   * @return array
   *   Any unknown values found in the values array.
   */
  protected function extractNewValues(array $values) {
    $allowed_values = $this->fieldDefinition->getSetting('allowed_values');
    $new_values = [];
    foreach ($values as $value) {
      if (!empty($value) && !isset($allowed_values[$value])) {
        $new_values[] = $value;
      }
    }

    return $new_values;
  }

  /**
   * Adds new values to the allowed values for this field.
   *
   * @param array $values_to_add
   *   The values to add to the allowed values.
   */
  protected function addNewValuesToAllowedValues(array $values_to_add) {
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $field_name = $this->fieldDefinition->getName();
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load("$entity_type.$field_name");
    $allowed_values = $storage->getSetting('allowed_values');

    foreach ($values_to_add as $value) {
      if (!isset($allowed_values[$value])) {
        $allowed_values[$value] = $value;
      }
    }

    if ($allowed_values !== $storage->getSetting('allowed_values')) {
      $storage->setSetting('allowed_values', $allowed_values)->save();
      drupal_static_reset('options_allowed_values');
    }
  }

}
