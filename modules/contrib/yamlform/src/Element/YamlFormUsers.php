<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides a users entity reference form element.
 *
 * @FormElement("yamlform_users")
 */
class YamlFormUsers extends EntityAutocomplete {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);

    $info['#target_type'] = 'user';
    $info['#selection_settings'] = ['include_anonymous' => FALSE];
    $info['#tags'] = TRUE;
    $info['#default_value'] = [];
    $info['#element_validate'] = [
      [$class, 'validateEntityAutocomplete'],
      [$class, 'validateYamlFormUsers'],
    ];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($element['#default_value']) {
      if (!(reset($element['#default_value']) instanceof EntityInterface)) {
        $element['#default_value'] = User::loadMultiple($element['#default_value']);
      }
    }
    return parent::valueCallback($element, $input, $form_state);
  }

  /**
   * Form element validation handler for yamlform_users elements.
   */
  public static function validateYamlFormUsers(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = $form_state->getValue($element['#parents'], []);
    $uids = [];
    if ($value) {
      foreach ($value as $item) {
        if (isset($item)) {
          $uids[] = $item['target_id'];
        }
      }
    }
    $form_state->setValueForElement($element, $uids);
  }

}
