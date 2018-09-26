<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormElementBase;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a 'entity_autocomplete' element.
 *
 * @YamlFormElement(
 *   id = "entity_autocomplete",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Element!EntityAutocomplete.php/class/EntityAutocomplete",
 *   label = @Translation("Entity autocomplete"),
 *   category = @Translation("Entity reference elements"),
 * )
 */
class EntityAutocomplete extends YamlFormElementBase implements YamlFormEntityReferenceInterface {

  use YamlFormEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Entity reference settings.
      'target_type' => '',
      'selection_handler' => 'default',
      'selection_settings' => [],
      'tags' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value']) && (!empty($element['#default_value']) || $element['#default_value'] === 0)) {
      $target_storage = $this->entityTypeManager->getStorage($element['#target_type']);
      if ($this->hasMultipleValues($element)) {
        $entity_ids = $this->getTargetEntityIds($element['#default_value']);
        $element['#default_value'] = ($entity_ids) ? $target_storage->loadMultiple($entity_ids) : [];
      }
      else {
        $element['#default_value'] = $target_storage->load($element['#default_value']) ?: NULL;
      }
    }
    else {
      $element['#default_value'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    return (!empty($element['#tags'])) ? TRUE : parent::hasMultipleValues($element);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);
    // If #tags (aka multiple entities) use #after_builder to set #element_value
    // which must be executed after
    // \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete().
    if ($this->hasMultipleValues($element)) {
      $element['#after_build'][] = [get_class($this), 'afterBuildEntityAutocomplete'];
    }

    // If selection handler include auto_create when need to also set it for
    // the $element.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete
    if (!empty($element['#selection_settings']['auto_create_bundle'])) {
      $element['#autocreate']['bundle'] = $element['#selection_settings']['auto_create_bundle'];
    }
  }

  /**
   * Form API callback. After build set the #element_validate handler.
   */
  public static function afterBuildEntityAutocomplete(array $element, FormStateInterface $form_state) {
    $element['#element_validate'][] = ['\Drupal\yamlform\Plugin\YamlFormElement\EntityAutocomplete', 'validateEntityAutocomplete'];
    return $element;
  }

  /**
   * Form API callback. Remove target id property and create an array of entity ids.
   */
  public static function validateEntityAutocomplete(array &$element, FormStateInterface $form_state) {
    $name = $element['#name'];
    $value = $form_state->getValue($name);
    if (is_array($value) && !empty($value)) {
      $entity_ids = [];
      foreach ($value as $item) {
        if (isset($item['target_id'])) {
          $entity_ids[] = $item['target_id'];
        }
        elseif (isset($item['entity'])) {
          // If #auto_create is set then we need to save the entity and get
          // the new entity's id.
          // @todo Decide what level of access controls are needed to allow
          // users to create entities.
          $entity = $item['entity'];
          $entity->save();
          $entity_ids[] = $entity->id();
        }
      }
      $form_state->setValueForElement($element, $entity_ids);
    }
  }

}
