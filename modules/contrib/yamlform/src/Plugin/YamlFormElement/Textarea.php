<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'textarea' element.
 *
 * @YamlFormElement(
 *   id = "textarea",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textarea.php/class/Textarea",
 *   label = @Translation("Textarea"),
 *   category = @Translation("Basic elements"),
 *   multiline = TRUE,
 * )
 */
class Textarea extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      // General settings.
      'description' => '',
      'default_value' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'placeholder' => '',
      'rows' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'counter_type' => '',
      'counter_maximum' => '',
      'counter_message' => '',
      // Submission display.
      'format' => $this->getDefaultFormat(),
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['counter_message']);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array &$element, $value, array $options = []) {
    return [
      '#markup' => nl2br(new HtmlEscapedText($value)),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['general']['default_value']['#type'] = 'textarea';
    return $form;
  }

}
