<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\Field\FieldWidget\GeofieldDefaultWidget.
 */

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget implementation of the 'geofield_default' widget.
 *
 * @FieldWidget(
 *   id = "geofield_default",
 *   label = @Translation("Geofield"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldDefaultWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#type' => 'textarea',
      '#default_value' => $items[$delta]->value ?: NULL,
    );
    return array('value' => $element);
  }
}
