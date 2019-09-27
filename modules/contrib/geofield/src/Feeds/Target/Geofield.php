<?php

namespace Drupal\geofield\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a geofield field mapper.
 *
 * @FeedsTarget(
 *   id = "geofield_feeds_target",
 *   field_types = {"geofield"}
 * )
 */
class Geofield extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    $definition = FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('lat')
      ->addProperty('lon');
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values) {
    $return = array();
    $coordinates = array();
    $coordinates_counter = 0;

    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);

        foreach ($columns as $key => $items) {
          foreach ($items as $item) {
            $coordinates[$coordinates_counter][$key][] = $item;
          }
          $coordinates_counter = 0;
        }

      }
      catch (EmptyFeedException $e) {
        drupal_set_message($e->getMessage(), 'error');

        return FALSE;
      };

    }
    if (isset($coordinates)) {
      foreach ($coordinates as $coordinate) {
        $count_of_coordinates = count($coordinate['lat']);

        for ($i = 0; $i < $count_of_coordinates; $i++) {
          $return[]['value'] = "POINT (" . $coordinate['lon'][$i] . " " . $coordinate['lat'][$i] . ")";
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    // Here is been preparing values for coordinates.
    foreach ($values as $column => $value) {
      $separated_coordinates = explode(" ", $value);
      $values[$column] = array();

      foreach ($separated_coordinates as $coordinate) {
        $values[$column][] = (float) $coordinate;
      }
    }

    // Latitude and Longitude should be a pair, if not throw EmptyFeedException.
    if (count($values['lat']) != count($values['lon'])) {
      throw new EmptyFeedException('Latitude and Longitude should be a pair. Change your file and import again.');
    }
  }

}
