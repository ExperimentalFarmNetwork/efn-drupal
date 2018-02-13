<?php

namespace Drupal\yamlform\Plugin\YamlFormElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormSubmissionInterface;
use \Drupal\yamlform\Element\YamlFormMessage as YamlFormMessageElement;

/**
 * Provides a 'yamlform_message' element.
 *
 * @YamlFormElement(
 *   id = "yamlform_message",
 *   label = @Translation("Message"),
 *   category = @Translation("Markup elements"),
 * )
 */
class YamlFormMessage extends YamlFormMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Attributes.
      'attributes' => [],
      // Message settings.
      'message_type' => 'status',
      'message_message' => '',
      'message_close' => FALSE,
      'message_close_effect' => 'slide',
      'message_id' => '',
      'message_storage' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['message_message']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, YamlFormSubmissionInterface $yamlform_submission) {
    parent::prepare($element, $yamlform_submission);

    if (!empty($element['#message_storage']) && empty($element['#message_id'])) {
      // Use
      // [yamlform:id]--[source_entity:type]-[source_entity:id]--[element:key]
      // as the message id.
      $id = [];
      if ($yamlform = $yamlform_submission->getYamlForm()) {
        $id[] = $yamlform->id();
      }
      if ($source_entity = $yamlform_submission->getSourceEntity()) {
        $id[] = $source_entity->getEntityTypeId() . '-' . $source_entity->id();
      }
      $id[] = $element['#yamlform_key'];
      $element['#message_id'] = implode('--', $id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['markup']['#title'] = $this->t('Message settings');
    $form['markup']['message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Message type'),
      '#options' => [
        'status' => t('Status'),
        'error' => t('Error'),
        'warning' => t('Warning'),
        'info' => t('Info'),
      ],
    ];
    $form['markup']['message_message'] = [
      '#type' => 'yamlform_html_editor',
      '#title' => $this->t('Message content'),
    ];
    $form['markup']['message_close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to close the message.'),
      '#return_value' => TRUE,
    ];
    $form['markup']['message_close_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Message close effect'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'slide' => $this->t('Slide'),
        'fade' => $this->t('Fade'),
      ],
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['markup']['message_storage'] = [
      '#type' => 'radios',
      '#title' => $this->t('Message storage'),
      '#options' => [
        YamlFormMessageElement::STORAGE_NONE => $this->t('None: Message state is never stored.'),
        YamlFormMessageElement::STORAGE_SESSION => $this->t('Session storage: Message state is reset after the browser is closed.'),
        YamlFormMessageElement::STORAGE_LOCAL => $this->t('Local storage: Message state persists after the browser is closed.'),
        YamlFormMessageElement::STORAGE_USER => $this->t("User data: Message state is saved to the current user's data. (Applies to authenticated users only)"),
        YamlFormMessageElement::STORAGE_STATE => $this->t("State API: Message state is saved to the site's system state. (Applies to authenticated users only)"),
      ],
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['markup']['message_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message ID'),
      '#description' => $this->t("Unique ID used to store the message's closed state. Please enter only lower-case letters, numbers, dashes, and underscores.") . '<br/>' .
      $this->t('Defaults to: %value', ['%value' => '[yamlform:id]--[element:key]']),
      '#pattern' => '/^[a-z0-9-_]+$/',
      '#states' => [
        'visible' => [':input[name="properties[message_close]"]' => ['checked' => TRUE]],
      ],
    ];
    return $form;
  }

}
