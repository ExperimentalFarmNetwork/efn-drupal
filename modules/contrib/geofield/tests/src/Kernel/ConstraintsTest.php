<?php

namespace Drupal\Tests\geofield\Kernel;

use Drupal\geofield\Plugin\Validation\Constraint\GeoConstraint;
use Drupal\geofield\Plugin\Validation\Constraint\GeoConstraintValidator;
use Drupal\geofield\GeoPHP\GeoPHPWrapper;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests geofield constraints.
 *
 * @group geofield
 */
class ConstraintsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['geofield'];

  /**
   * Tests GeoType constraint.
   *
   * @covers \Drupal\geofield\Plugin\Validation\Constraint\GeoConstraintValidator
   * @covers \Drupal\geofield\Plugin\Validation\Constraint\GeoConstraint
   *
   * @dataProvider geoProvider
   */
  public function testGeoConstraint($coordinates, $expected_violation_count) {
    // Check message in constraint.
    $constraint = new GeoConstraint();
    $this->assertEquals('"@value" is not a valid geospatial content.', $constraint->message, 'Correct constraint message found.');

    $execution_context = $this->getMockBuilder('\Drupal\Core\TypedData\Validation\ExecutionContext')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected_violation_count) {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation')
        ->with($constraint->message, ['@value' => $coordinates]);
    }
    else {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation');
    }

    $geophp_wrapper = new GeoPHPWrapper();
    $validator = new GeoConstraintValidator($geophp_wrapper);
    $validator->initialize($execution_context);

    $validator->validate($coordinates, $constraint);
  }

  /**
   * Provides test data for testGeoConstraint().
   */
  public function geoProvider() {
    return [
      'valid POINT' => ['POINT (40 -3)', 0],
      'invalid POAINT' => ['POAINT (40 -3)', 1],
      'invalid POINT' => ['POINT (40 -A)', 1],
    ];
  }

}
