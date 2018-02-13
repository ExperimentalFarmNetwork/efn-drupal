<?php

namespace Drupal\yamlform;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides a form to manage access.
 */
class YamlFormEntityAccessForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->entity;
    $access = $yamlform->getAccessRules();
    $permissions = [
      'create' => $this->t('Create form submissions'),
      'view_any' => $this->t('View all form submissions'),
      'update_any' => $this->t('Update all form submissions'),
      'delete_any' => $this->t('Delete all form submissions'),
      'purge_any' => $this->t('Purge all form submissions'),
      'view_own' => $this->t('View own form submissions'),
      'update_own' => $this->t('Update own form submissions'),
      'delete_own' => $this->t('Delete own form submissions'),
    ];
    $form['access']['#tree'] = TRUE;
    foreach ($permissions as $name => $title) {
      $form['access'][$name] = [
        '#type' => ($name === 'create') ? 'fieldset' : 'details',
        '#title' => $title,
        '#open' => ($access[$name]['roles'] || $access[$name]['users']) ? TRUE : FALSE,
      ];
      $form['access'][$name]['roles'] = [
        '#type' => 'yamlform_roles',
        '#title' => $this->t('Roles'),
        '#include_anonymous' => ($name == 'create') ? TRUE : FALSE,
        '#default_value' => $access[$name]['roles'],
      ];
      $form['access'][$name]['users'] = [
        '#type' => 'yamlform_users',
        '#title' => $this->t('Users'),
        '#default_value' => $access[$name]['users'] ? User::loadMultiple($access[$name]['users']) : [],
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    // Don't display delete button.
    unset($element['delete']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $access = $form_state->getValue('access');

    /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
    $yamlform = $this->getEntity();
    $yamlform->setAccessRules($access);
    $yamlform->save();

    $this->logger('yamlform')->notice('Form access @label saved.', ['@label' => $yamlform->label()]);
    drupal_set_message($this->t('Form access %label saved.', ['%label' => $yamlform->label()]));
  }

}
