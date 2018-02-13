<?php

/**
 * @file
 * Contains \Drupal\markup\Plugin\Field\FieldFormatter\MarkupDefaultFormatter.
 */

namespace Drupal\markup\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'markup_default' formatter.
 *
 * @FieldFormatter(
 *   id = "markup",
 *   label = @Translation("Markup"),
 *   field_types = {
 *     "markup"
 *   }
 * )
 */
class MarkupFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element[] = [
      '#type'   => 'processed_text',
      '#text'   => $this->fieldDefinition->getSetting('markup')['value'],
      '#format' => $this->fieldDefinition->getSetting('markup')['format'],
    ];

    return $element;
  }

}
