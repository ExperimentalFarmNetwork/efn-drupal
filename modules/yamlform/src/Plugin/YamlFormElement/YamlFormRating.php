<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\Element\YamlFormRating as YamlFormRatingElement;
use Drupal\yamlform\YamlFormSubmissionInterface;

/**
 * Provides a 'rating' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_rating",
 *   label = @Translation("Rating"),
 *   category = @Translation("Advanced elements"),
 * )
 */
class YamlFormRating extends Range {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();
    unset(
      $properties['range__output'],
      $properties['range__output_prefix'],
      $properties['range__output_suffix']
    );
    $properties += [
      // General settings.
      'default_value' => 0,
      // Rating settings.
      'star_size' => 'medium',
      'reset' => FALSE,
    ];
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    if (!isset($element['#step'])) {
      $element['#step'] = 1;
    }
    parent::prepare($element, $yamlform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array &$element, $value, array $options = []) {
    $format = $this->getFormat($element);

    switch ($format) {
      case 'star':
        // Always return the raw value when the rating widget is included in an
        // email.
        if (!empty($options['email'])) {
          return parent::formatText($element, $value, $options);
        }

        $build = [
          '#value' => $value,
          '#readonly' => TRUE,
        ] + $element;
        return YamlFormRatingElement::buildRateIt($build);

      default:
        return parent::formatHtml($element, $value, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormat() {
    return 'star';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormats() {
    return parent::getFormats() + [
      'star' => $this->t('Star'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['number']['#title'] = $this->t('Rating settings');
    $form['number']['star_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Star size'),
      '#options' => [
        'small' => $this->t('Small (@size)', ['@size' => '16px']),
        'medium' => $this->t('Medium (@size)', ['@size' => '24px']),
        'large' => $this->t('Large (@size)', ['@size' => '32px']),
      ],
      '#required' => TRUE,
    ];
    $form['number']['reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show reset button'),
      '#description' => $this->t('If checked, a reset button will be placed before the rating element.'),
      '#return_value' => TRUE,
    ];
    return $form;
  }

}
