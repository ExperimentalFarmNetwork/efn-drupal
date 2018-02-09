<?php

namespace Drupal\yamlform_ui\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\yamlform\YamlFormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides an edit form for a form element.
 */
class YamlFormUiElementEditForm extends YamlFormUiElementFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, YamlFormInterface $yamlform = NULL, $key = NULL) {
    $this->element = $yamlform->getElementDecoded($key);
    if ($this->element === NULL) {
      throw new NotFoundHttpException();
    }

    // Handler changing element type.
    if ($type = $this->getRequest()->get('type')) {
      $yamlform_element = $this->getYamlFormElement();
      $related_types = $yamlform_element->getRelatedTypes($this->element);
      if (!isset($related_types[$type])) {
        throw new NotFoundHttpException();
      }
      $this->originalType = $this->element['#type'];
      $this->element['#type'] = $type;
    }

    // Issue: #title is display as modal dialog's title and can't be escaped.
    // Workaround: Filter and define @title as safe markup.
    $form['#title'] = $this->t('Edit @title element', [
      '@title' => (!empty($this->element['#title'])) ? new FormattableMarkup(Xss::filterAdmin($this->element['#title']), []) : $key,
    ]);

    $this->action = $this->t('updated');
    return parent::buildForm($form, $form_state, $yamlform, $key);
  }

}
