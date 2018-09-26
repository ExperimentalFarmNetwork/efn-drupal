<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;

/**
 * Provides a 'yamlform_markup' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_markup",
 *   label = @Translation("HTML markup"),
 *   category = @Translation("Markup elements"),
 *   states_wrapper = TRUE,
 * )
 */
class YamlFormMarkup extends YamlFormMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Markup settings.
      'markup' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array &$element, $value, array $options = []) {
    $element['#markup'] = MailFormatHelper::htmlToText($element['#markup']);
    return parent::buildText($element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['markup']  = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('HTML markup'),
      '#description' => $this->t('Enter custom HTML into your form.'),
    ];
    return $form;
  }

}
