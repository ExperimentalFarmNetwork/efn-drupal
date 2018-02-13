<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a delete form.
 */
class YamlFormEntityDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to the delete this form.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];
    return $form;
  }

}
