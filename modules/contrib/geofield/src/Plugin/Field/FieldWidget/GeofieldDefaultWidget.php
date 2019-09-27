<?php

namespace Drupal\geofield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget implementation of the 'geofield_default' widget.
 *
 * @FieldWidget(
 *   id = "geofield_default",
 *   label = @Translation("Geofield (WKT)"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class GeofieldDefaultWidget extends GeofieldBaseWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'textarea',
      '#default_value' => $items[$delta]->value ?: NULL,
    ];
    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      /* @var \Geometry $geom */
      if ($geom = $this->geoPhpWrapper->load($value['value'])) {
        $values[$delta]['value'] = $geom->out('wkt');
      }
    }
    return $values;
  }

}
