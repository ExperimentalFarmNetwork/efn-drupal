<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a 'autocomplete' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class YamlFormAutocomplete extends TextField {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Autocomplete settings.
      'autocomplete_existing' => FALSE,
      'autocomplete_items' => [],
      'autocomplete_limit' => 10,
      'autocomplete_match' => 3,
      'autocomplete_match_operator' => 'CONTAINS',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);

    $has_items = !empty($element['#autocomplete_items']);
    // Query form submission for existing items.
    if (!$has_items && !empty($element['#autocomplete_existing'])) {
      $has_items = \Drupal::database()->select('yamlform_submission_data')
        ->fields('yamlform_submission_data', ['value'])
        ->condition('yamlform_id', $yamlform_submission->getYamlForm()->id())
        ->condition('name', $element['#yamlform_key'])
        ->condition('value', '', '!=')
        ->execute()
        ->fetchField();
    }

    if ($has_items && isset($element['#yamlform_key'])) {
      $element['#autocomplete_route_name'] = 'yamlform.element.autocomplete';
      $element['#autocomplete_route_parameters'] = [
        'yamlform' => $yamlform_submission->getYamlForm()->id(),
        'key' => $element['#yamlform_key'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['autocomplete'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Autocomplete settings'),
    ];
    $form['autocomplete']['autocomplete_items'] = [
      '#type' => 'yamlform_element_options',
      '#custom__type' => 'yamlform_multiple',
      '#title' => $this->t('Autocomplete values'),
    ];
    $form['autocomplete']['autocomplete_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include existing submission values.'),
      '#description' => $this->t("If checked, all existing submission values will be visible to the form's users."),
      '#return_value' => TRUE,
    ];
    $form['autocomplete']['autocomplete_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete limit'),
      '#description' => $this->t("The maximum number of matches to be displayed."),
      '#min' => 1,
    ];
    $form['autocomplete']['autocomplete_match'] = [
      '#type' => 'number',
      '#title' => $this->t('Autocomplete minimum number of characters'),
      '#description' => $this->t('The minimum number of characters a user must type before a search is performed.'),
      '#min' => 1,
    ];
    $form['autocomplete']['autocomplete_match_operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Autocomplete matching operator'),
      '#description' => $this->t('Select the method used to collect autocomplete suggestions.'),
      '#options' => [
        'STARTS_WITH' => $this->t('Starts with'),
        'CONTAINS' => $this->t('Contains'),
      ],
    ];
    return $form;
  }

}
