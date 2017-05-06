<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element for message.
 *
 * @FormElement("yamlform_message")
 */
class YamlFormMessage extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#message_type' => 'status',
      '#message_message' => '',
      '#status_headings' => [],
      '#process' => [
        [$class, 'processMessage'],
      ],
      '#theme' => 'status_messages',
      '#states' => [],
    ];
  }

  /**
   * Processes a messages element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   container.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processMessage(&$element, FormStateInterface $form_state, &$complete_form) {
    $message_type = $element['#message_type'];
    $message_message = $element['#message_message'];

    // Build the messages render array.
    $messages = [];
    $messages[] = (!is_array($message_message)) ? ['#markup' => $message_message] : $message_message;
    foreach (Element::children($element) as $key) {
      $messages[] = $element[$key];
    }
    $element['#message_list'][$message_type][] = $messages;
    $element['#status_headings'] += [
      'status' => t('Status message'),
      'error' => t('Error message'),
      'warning' => t('Warning message'),
    ];
    $element['#attached']['library'][] = 'yamlform/yamlform.element.message';

    // Set #states and .js-form-item (which is needed by  #states).
    // @see core/misc/states.js
    yamlform_process_states($element);

    return $element;
  }

}
