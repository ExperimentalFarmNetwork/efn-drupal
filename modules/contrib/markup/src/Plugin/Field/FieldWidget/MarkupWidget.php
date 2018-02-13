<?php

/**
 * @file
 * Contains \Drupal\markup\Plugin\Field\FieldWidget\ImageWidget.
 */

namespace Drupal\markup\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'markup' widget.
 *
 * @FieldWidget(
 *   id = "markup",
 *   label = @Translation("Markup"),
 *   field_types = {
 *     "markup"
 *   }
 * )
 */
class MarkupWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['markup'] = [
      '#type'   => 'processed_text',
      '#text'   => $this->fieldDefinition->getSetting('markup')['value'],
      '#format' => $this->fieldDefinition->getSetting('markup')['format'],
    ];

    return $element;
  }

}
