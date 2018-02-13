<?php

namespace Drupal\yamlform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for a likert scale.
 *
 * @FormElement("yamlform_likert")
 */
class YamlFormLikert extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processYamlFormLikert'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#required' => FALSE,
      '#questions' => [],
      // Using #answers insteads of #options to prevent triggering
      // \Drupal\Core\Form\FormValidator::performRequiredValidation().
      '#answers' => [],
      '#na_answer' => FALSE,
      '#na_answer_text' => '',
      '#na_answer_value' => '',
    ];
  }

  /**
   * Processes a likert scale form element.
   */
  public static function processYamlFormLikert(&$element, FormStateInterface $form_state, &$complete_form) {
    // Get answer with optional N/A.
    self::processYamlFormLikertAnswers($element);

    // Build header.
    $header = [
      'likert_question' => ['question' => FALSE],
    ] + $element['#answers'];

    // Randomize questions.
    if (!empty($element['#questions_randomize'])) {
      shuffle($element['#questions']);
    }

    // Build rows.
    $rows = [];
    foreach ($element['#questions'] as $question_key => $question_title) {
      $value = (isset($element['#value'][$question_key])) ? $element['#value'][$question_key] : NULL;
      $row = [];
      // Must format the label as an item so that inline form errors will be
      // displayed.
      $row['likert_question'] = [
        '#type' => 'item',
        '#title' => $question_title,
        // Must include an empty <span> so that the item's value is
        // not required.
        '#value' => '<span></span>',
        '#required' => $element['#required'],
      ];
      foreach ($element['#answers'] as $answer_key => $answer_title) {
        $row[$answer_key] = [
          '#parents' => [$element['#name'], $question_key],
          '#type' => 'radio',
          '#title' => $answer_title,
          '#title_display' => 'after',
          // Must cast values as strings to prevent NULL and empty strings.
          // from being evaluated as 0.
          '#return_value' => (string) $answer_key,
          '#value' => (string) $value,
        ];
      }
      $rows[$question_key] = $row;
    }

    $element['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['yamlform-likert-table'],
        'data-likert-answers-count' => count($element['#answers']),
      ],
    ] + $rows;

    // Build table element with selected properties.
    $properties = [
      '#states',
      '#sticky',
    ];
    $element['table'] += array_intersect_key($element, array_combine($properties, $properties));

    $element['#tree'] = TRUE;
    $element['#element_validate'] = [[get_called_class(), 'validateYamlFormLikert']];
    $element['#attached']['library'][] = 'yamlform/yamlform.element.likert';

    return $element;
  }

  /**
   * Get likert element's answer which can include an N/A option.
   *
   * @param array $element
   *   The element.
   */
  public static function processYamlFormLikertAnswers(array &$element) {
    if (empty($element['#na_answer']) || empty($element['#answers'])) {
      return;
    }

    $na_value = (!empty($element['#na_answer_value'])) ? $element['#na_answer_value'] : (string) t('N/A');
    $na_text = (!empty($element['#na_answer_text'])) ? $element['#na_answer_text'] : $na_value;
    $element['#answers'] += [
      $na_value => $na_text,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $default_value = [];
    foreach ($element['#questions'] as $question_key => $question_title) {
      $default_value[$question_key] = NULL;
    }

    if ($input === FALSE) {
      $element += ['#default_value' => []];
      return $element['#default_value'] + $default_value;
    }
    $value = $default_value;
    foreach ($value as $allowed_key => $default) {
      if (isset($input[$allowed_key]) && is_scalar($input[$allowed_key])) {
        $value[$allowed_key] = (string) $input[$allowed_key];
      }
    }
    return $value;
  }

  /**
   * Validates a likert element.
   */
  public static function validateYamlFormLikert(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];

    if (!empty($element['#required'])) {
      foreach ($element['#questions'] as $question_key => $question_title) {
        if (empty($value[$question_key])) {
          $form_state->setError($element['table'][$question_key]['likert_question'], t('@name field is required.', ['@name' => $question_title]));
        }
      }
    }

    $form_state->setValueForElement($element, $value);
  }

}
