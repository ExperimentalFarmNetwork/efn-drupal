<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\Validation\Constraint\GeoConstraint.
 */

namespace Drupal\geofield\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Validation constraint for geospatial values.
 *
 * @Constraint(
 *   id = "GeoType",
 *   label = @Translation("Geo data valid for geofield type.", context = "Validation"),
 * )
 */
class GeoConstraint extends Constraint implements ConstraintValidatorInterface {

  public $message = '"@value" is not a valid geospatial content.';

  /**
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * {@inheritDoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return get_class($this);
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if (isset($value)) {
      $valid_geometry = TRUE;

      try {
        if (!\Drupal::service('geofield.geophp')->load($value, 'wkt')) {
          $valid_geometry = FALSE;
        }
      }
      catch (\Exception $e) {
        $valid_geometry = FALSE;
      }

      if (!$valid_geometry) {
        $this->context->addViolation($this->message, array('@value' => $value));
      }
    }
  }
}
