# Geocoder 8.x-2.x

This is the Geocoder module for Drupal 8 rewritten using the
Geocoder PHP library.

# Requirements
* [Composer](https://getcomposer.org/)
* [Geophp module](https://drupal.org/project/geophp) >= 8.x-1.x
* [Geofield](https://drupal.org/project/geofield) >= 8.x-1.x

# Installation
* Make sure to read this page: https://www.drupal.org/node/2404989
* Make sure to add the drupal.org repository: ```composer
  config repositories.drupal composer https://packages.drupal.org/8```
* Download the module: ```composer require drupal/geocoder```
* Enable the module via drush ```drush en geocoder``` or the web interface.
* Enable the submodules ```geocoder_geofield``` or ```geocoder_field```.

# Features
* Provides options to geocode a field from a string into a geographic data format such as WKT, GeoJson, etc etc...
* The Provider and Dumper plugins are extendable through a custom module,
* All successful requests are cached by default.

# API

## Get a list of available Provider plugins

```php
\Drupal::service('plugin.manager.geocoder.provider')->getDefinitions()
```

## Get a list of available Dumper plugins

```php
\Drupal::service('plugin.manager.geocoder.dumper')->getDefinitions();
```

## Geocode a string

```php
$plugins = array('geonames', 'googlemaps', 'bingmaps');
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';
$options = array(
  'geonames' => array(), // array of options
  'googlemaps' => array(), // array of options
  'bingmaps' => array(), // array of options
);

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins, $options);
```

## Reverse geocode coordinates

```php
$plugins = array('freegeoip', 'geonames', 'googlemaps', 'bingmaps');
$lat = '37.422782';
$lon = '-122.085099';
$options = array(
  'freegeoip' => array(), // array of options
  'geonames' => array(), // array of options
  'googlemaps' => array(), // array of options
  'bingmaps' => array(), // array of options
);

$addressCollection = \Drupal::service('geocoder')->reverse($lat, $lon, $plugins, $options);
```

## Return format

Both ```Geocoder::geocode()``` and ```Geocoder::reverse()```
return the same object: ```Geocoder\Model\AddressCollection```,
which is itself composed of ```Geocoder\Model\Address```.

You can transform those objects into arrays. Example:

```php
$plugins = array('geonames', 'googlemaps', 'bingmaps');
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';
$options = array(
  'geonames' => array(), // array of options
  'googlemaps' => array(), // array of options
  'bingmaps' => array(), // array of options
);

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins, $options);
$address_array = $addressCollection->first()->toArray();

// You can play a bit more with the API

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins, $options);
$latitude = $addressCollection->first()->getCoordinates()->getLatitude();
$longitude = $addressCollection->first()->getCoordinates()->getLongitude();
```

You can also convert these to different formats using the Dumper plugins.
Get the list of available Dumper by doing:

```php
\Drupal::service('plugin.manager.geocoder.dumper')->getDefinitions();
```

Here's an example on how to use a Dumper

```php
$plugins = array('geonames', 'googlemaps', 'bingmaps');
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins);
$geojson = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geojson')->dump($addressCollection->first());
```

There's also a dumper for GeoPHP, here's how to use it

```php
$plugins = array('geonames', 'googlemaps', 'bingmaps');
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins);
$geometry = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geometry')->dump($addressCollection->first());
```

# Links
* [Composer](https://getcomposer.org/)
* [Drush](http://drush.org)
* [Geocoder PHP library](http://geocoder-php.org/)
* [Geocoder module](https://www.drupal.org/project/geocoder)
