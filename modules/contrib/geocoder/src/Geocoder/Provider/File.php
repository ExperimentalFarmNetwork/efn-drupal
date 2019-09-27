<?php

namespace Drupal\geocoder\Geocoder\Provider;

use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Provider\AbstractProvider;
use Geocoder\Provider\Provider;

/**
 * Provides a file handler to be used by 'file' plugin.
 */
class File extends AbstractProvider implements Provider {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function geocode($filename) {
    // Check file type exists and is a JPG (IMAGETYPE_JPEG) before exif_read.
    if (file_exists($filename) && exif_imagetype($filename) == 2 && $exif = @exif_read_data($filename)) {
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
  protected function getGpsExif($coordinate, $hemisphere) {
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
  public function reverse($latitude, $longitude) {
    throw new UnsupportedOperation('The File plugin is not able to do reverse geocoding.');
  }

}
