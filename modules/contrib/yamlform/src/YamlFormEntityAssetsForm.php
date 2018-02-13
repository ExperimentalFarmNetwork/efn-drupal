<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to inject CSS and JS assets.
 */
class YamlFormEntityAssetsForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->entity;

    $form['css'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom CSS'),
      '#open' => TRUE,
    ];
    $form['css']['css'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'css',
      '#title' => $this->t('CSS'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter custom CSS to be attached to the form.'),
      '#default_value' => $yamlform->getCss(),
    ];
    $form['javascript'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom JavaScript'),
      '#open' => TRUE,
    ];
    $form['javascript']['javascript'] = [
      '#type' => 'yamlform_codemirror',
      '#mode' => 'javascript',
      '#title' => $this->t('JavaScript'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter custom JavaScript to be attached to the form.'),
      '#default_value' => $yamlform->getJavaScript(),
    ];
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();
    $yamlform->setCss($form_state->getValue('css'));
    $yamlform->setJavaScript($form_state->getValue('javascript'));
    $yamlform->save();

    $this->logger('yamlform')->notice('Form assets for @label saved.', ['@label' => $yamlform->label()]);
    drupal_set_message($this->t('Form assets for %label saved.', ['%label' => $yamlform->label()]));
  }

}
