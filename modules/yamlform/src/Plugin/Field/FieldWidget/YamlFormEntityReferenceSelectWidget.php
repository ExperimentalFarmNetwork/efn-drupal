<?php

namespace Drupal\yamlform\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormInterface;

/**
 * Plugin implementation of the 'yamlform_entity_reference_select' widget.
 *
 * @FieldWidget(
 *   id = "yamlform_entity_reference_select",
 *   label = @Translation("Select list"),
 *   description = @Translation("A select menu field."),
 *   field_types = {
 *     "yamlform"
 *   }
 * )
 *
 * @see \Drupal\yamlform\Plugin\Field\FieldWidget\YamlFormEntityReferenceAutocompleteWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget
 */
class YamlFormEntityReferenceSelectWidget extends YamlFormEntityReferenceAutocompleteWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Convert 'entity_autocomplete' to 'yamlform_entity_select' element.
    $element['target_id']['#type'] = 'yamlform_entity_select';

    // Set empty option.
    if (empty($element['#required'])) {
      $element['target_id']['#empty_option'] = $this->t('- Select -');
      $element['target_id']['#empty_value'] = '';
    }

    // Convert default_value's YamlForm to a simple entity_id.
    if (!empty($element['target_id']['#default_value']) && $element['target_id']['#default_value'] instanceof YamlFormInterface) {
      $element['target_id']['#default_value'] = $element['target_id']['#default_value']->id();
    }

    // Remove properties that are not applicable.
    unset($element['target_id']['#size']);
    unset($element['target_id']['#maxlength']);
    unset($element['target_id']['#placeholder']);

    $element['#element_validate'] = [[get_class($this), 'validateYamlFormEntityReferenceSelectWidget']];

    return $element;
  }

  /**
   * Form element validation handler for entity_select elements.
   */
  public static function validateYamlFormEntityReferenceSelectWidget(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Below prevents the below error.
    // Fatal error: Call to a member function uuid() on a non-object in
    // core/lib/Drupal/Core/Field/EntityReferenceFieldItemList.php.
    $value = (!empty($element['target_id']['#value'])) ? $element['target_id']['#value'] : NULL;
    $form_state->setValueForElement($element['target_id'], $value);
  }

}
