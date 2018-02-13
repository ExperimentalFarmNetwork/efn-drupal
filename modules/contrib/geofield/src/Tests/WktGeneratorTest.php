<?php

/**
 * @file
 * Contains \Drupal\geofield\Tests\WktGeneratorTest.
 */

namespace Drupal\geofield\Tests;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests WktGenerator.
 *
 * @group geofield
 */
class WktGeneratorTest extends KernelTestBase {

  /**
   * WKT Generator service.
   *
   * @var \Drupal\geofield\WktGenerator
   */
  public $wkt_generator;

  /**
   * Generic WKT point regex.
   *
   * @var string
   */
  public $point_regex = '/^POINT \([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)$/';

  /**
   * Generic WKT multipoint regex.
   *
   * @var string
   */
  public $multipoint_regex = '/^MULTIPOINT \((\([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\), )*(\([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\))\)$/';

  /**
   * Generic WKT linestring regex.
   *
   * @var string
   */
  public $linestring_regex = '/^LINESTRING \(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)$/';

  /**
   * Generic WKT multilinestring regex.
   *
   * @var string
   */
  public $multilinestring_regex = '/^MULTILINESTRING \((\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\), )*\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)\)$/';

  /**
   * Generic WKT polygon regex.
   *
   * @var string
   */
  public $polygon_regex = '/^POLYGON \(\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)\)$/';

  /**
   * Generic WKT multipolygon regex.
   *
   * @var string
   */
  public $multipolygon_regex = '/^MULTIPOLYGON \((\(\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)\), )*\(\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)\)\)$/';

  /**
   * {@inheritdoc}
   */
  public static $modules = array(
    'geofield',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->wkt_generator = \Drupal::service('geofield.wkt_generator');
  }

  /**
   * Tests the generation of WKT points and multipoints.
   */
  public function testPoint() {
    $point = $this->wkt_generator->WktGeneratePoint(array('3', '4'));
    $this->assertEqual('POINT (3 4)', $point, 'Point generated properly');

    $point = $this->wkt_generator->WktGeneratePoint();
    $match = preg_match($this->point_regex, $point);
    $this->assertTrue($match, 'Point generated properly');

    $multipoint = $this->wkt_generator->WktGenerateMultipoint();
    $match = preg_match($this->multipoint_regex, $multipoint);
    $this->assertTrue($match, 'Multipoint generated properly');
  }

  /**
   * Tests the generation of WKT linestrings and multilinestrings.
   */
  public function testLinestring() {
    $linestring = $this->wkt_generator->WktGenerateLinestring();
    $match = preg_match($this->linestring_regex, $linestring);
    $this->assertTrue($match, 'Linestring generated properly');

    $linestring = $this->wkt_generator->WktGenerateLinestring(array(7.34, -45.66));
    $match = preg_match('/^LINESTRING \(7.34 -45.66, ([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)$/', $linestring);
    $this->assertTrue($match, 'Linestring generated properly');

    $linestring = $this->wkt_generator->WktGenerateLinestring(NULL, 9);
    $match = preg_match('/^LINESTRING \(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, ){8}[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)$/', $linestring);
    $this->assertTrue($match, 'Linestring generated properly');

    $linestring = $this->wkt_generator->WktGenerateLinestring(array(7, 45), 6);
    $match = preg_match('/^LINESTRING \(7 45, ([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, ){4}[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)$/', $linestring);
    $this->assertTrue($match, 'Linestring generated properly');

    $multilinestring = $this->wkt_generator->WktGenerateMultilinestring();
    $match = preg_match($this->multilinestring_regex, $multilinestring);
    $this->assertTrue($match, 'Multilinestring generated properly');
  }

  /**
   * Tests the generation of WKT polygons and multipolygons.
   */
  public function testPolygon() {
    $polygon = $this->wkt_generator->WktGeneratePolygon();
    $match = preg_match($this->polygon_regex, $polygon);
    $this->assertTrue($match, 'Polygon generated properly');

    $polygon = $this->wkt_generator->WktGeneratePolygon(array(7.34, -45.66));
    $match = preg_match('/^POLYGON \(\(7.34 -45.66, ([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, )*7.34 -45.66\)\)$/', $polygon);
    $this->assertTrue($match, 'Polygon generated properly');

    $polygon = $this->wkt_generator->WktGeneratePolygon(NULL, 9);
    $match = preg_match('/^POLYGON \(\(([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, ){9}[-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\)\)$/', $polygon);
    $this->assertTrue($match, 'Polygon generated properly');

    $polygon = $this->wkt_generator->WktGeneratePolygon(array(7, 45), 6);
    $match = preg_match('/^POLYGON \(\(7 45, ([-]?[0-9]*\.?[0-9]+ [-]?[0-9]*\.?[0-9]+\, ){5}7 45\)\)$/', $polygon);
    $this->assertTrue($match, 'Polygon generated properly');

    $multipolygon = $this->wkt_generator->WktGenerateMultipolygon();
    $match = preg_match($this->multipolygon_regex, $multipolygon);
    $this->assertTrue($match, 'Multipolygon generated properly');
  }

  /**
   * Tests the generation of random WKT geometries.
   */
  public function testRandomGeometry() {
    $find = FALSE;
    $geometry = $this->wkt_generator->WktGenerateGeometry();
    $patterns = array(
      $this->point_regex,
      $this->multipoint_regex,
      $this->linestring_regex,
      $this->multilinestring_regex,
      $this->polygon_regex,
      $this->multipolygon_regex,
    );

    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $geometry)) {
        $find = TRUE;
        break;
      }
    }

    $this->assertTrue($find, 'Random geometry generated properly');
  }

}
