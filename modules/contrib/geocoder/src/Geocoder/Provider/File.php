<?php

declare(strict_types = 1);

namespace Drupal\geocoder\Geocoder\Provider;

use Geocoder\Collection;
use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

/**
 * Provides a file handler to be used by 'file' plugin.
 */
class File extends AbstractProvider implements Provider {

  /**
   * The name of the file that is being geocoded.
   *
   * @var string
   */
  protected $filename;

  /**
   * Constructs a new File provider.
   *
   * @param string $filename
   *   The name of the file to read from.
   */
  public function __construct(string $filename) {
    $this->filename = $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function geocodeQuery(GeocodeQuery $query): Collection {
    throw new \Exception('Update ' . __METHOD__);
    if ($exif = exif_read_data($filename)) {
      if (isset($exif['GPSLatitude']) && isset($exif['GPSLatitudeRef']) && $exif['GPSLongitude'] && $exif['GPSLongitudeRef']) {
        $latitude = $this->getGpsExif($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $longitude = $this->getGpsExif($exif['GPSLongitude'], $exif['GPSLongitudeRef']);

        return $this->returnResults([[
          'latitude' => $latitude,
          'longitude' => $longitude,
        ] + $this->getDefaults(),
        ]);
      }
    }

    // @todo Instead of throwing an exception this should now return an empty
    // Collection.
    throw new NoResult(sprintf('Could not find geo data in file: "%s".', basename($filename)));
  }

  /**
   * Retrieves the latitude and longitude from exif data.
   *
   * @param string $coordinate
   *   The coordinate.
   * @param string $hemisphere
   *   The hemisphere.
   *
   * @return float
   *   Return value based on coordinate and Hemisphere.
   */
  protected function getGpsExif(string $coordinate, string $hemisphere): float {
    for ($i = 0; $i < 3; $i++) {
      $part = explode('/', $coordinate[$i]);

      if (count($part) == 1) {
        $coordinate[$i] = $part[0];
      }
      elseif (count($part) == 2) {
        $coordinate[$i] = floatval($part[0]) / floatval($part[1]);
      }
      else {
        $coordinate[$i] = 0;
      }
    }

    list($degrees, $minutes, $seconds) = $coordinate;
    $sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
    $value = $sign * ($degrees + $minutes / 60 + $seconds / 3600);

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function reverseQuery(ReverseQuery $query): Collection {
    throw new UnsupportedOperation('The Image plugin is not able to do reverse geocoding.');
  }

}
