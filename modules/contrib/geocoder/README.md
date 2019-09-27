# Geocoder 8.x-2.x

This is a complete rewrite of the Geocoder module, based on the
[Geocoder PHP library](http://geocoder-php.org) - [version 3.x](https://github.com/geocoder-php/Geocoder/tree/3.x).

# Features
* Solid API based on [Geocoder PHP library](http://geocoder-php.org);
* Geocode and Reverse Geocode using one or multiple Geocoder providers
  (ArcGISOnline, BingMaps, File, GoogleMaps, MapQuest, Nominatim,
  OpeneStreetMap, etc);
* Results can be dumped into multiple formats such as WKT, GeoJson, etc
  ...</li>
* The Geocoder Provider and Dumper plugins are extendable through a custom
  module;</li>
* Submodule Geocoder Field provides Drupal fields widgets and formatters, with
  even more options;</li>
* [Geofield](https://www.drupal.org/project/geofield) and
  [Address](https://www.drupal.org/project/address) fields integration;
* Caching results capabilities, enabled by default;

# Requirements
* [Composer](https://getcomposer.org/), to add the module to your codebase (for
  more info refer to [Using Composer to manage Drupal site
  dependencies](https://www.drupal.org/node/2718229);
* [Drush](http://drush.org), to enable the module (and its dependencies) from
  the shell;
* No other external requirements for the main geocoder Module: the [Geocoder PHP
  library](http://geocoder-php.org) will be downloaded automatically via
  composer (see below);
* The embedded "Geocoder Geofield" submodule requires the [Geofield
  Module](https://www.drupal.org/project/geofield);
* The embedded "Geocoder Address" submodule requires the [Address
  Module](https://www.drupal.org/project/address);

# Installation and setup
* Download the module running the following shell command from your project root
  (at the composer.json file level):
  ```$ composer require drupal/geocoder```
  **Note:** this will also download the Geocoder PHP library as
  vendor/willdurand/geocoder
* Enable the module via [Drush](http://drush.org)  
 ```$ drush en geocoder```
 or the website back-end/administration interface;
* Eventually enable the submodules: ```geocoder_field``` and
  ```geocoder_geofield``` / ```geocoder_address```.
* From the module configuration page it is possible to setup caching and custom
  options for every available Geocoder Provider;

# Submodules
The geocoder submodules are needed to set-up and implement Geocode and Reverse
Geocode functionalities on Entity fields from the Drupal backend:
* The **geocoder_field** module adds the ability to setup Geocode operations
  on entity insert & edit operations among specific fields types so as field 
  Geo formatters, using all the available Geocoder Provider Plugins and Output 
  Geo Formats (via Dumpers). It also enables the File provider/formatter 
  functionalities for Geocoding valid Exif Geo data present into JPG images;
* The **geocoder_geofield** module provides integration with Geofield
  (module/field type) and the ability to both use it as target of Geocode or
  source of Reverse Geocode with the other fields. It also enables the
  provider/formatter functionalities for Geocoding valid GPX, KML and GeoJson
  data present into files contents;
* The **geocoder_address** module provides integration with Address
  (module/field type) and the ability to both use it as target of Reverse
  Geocode or source of Geocode with the other fields;

From the Geocoder configuration page it is possible to setup custom plugins 
options.

Throughout geocoder submodules **the following fields types are supported** 

###### for Geocode operations:

 * "text",
 * "text_long",
 * "text_with_summary",
 * "string",
 * "string_long",
 * "computed_string" (with "computed_field" module enabled);
 * "computed_string_long" (with "computed_field" module enabled);
 * "address" (with "address" module and "geocoder_address" sub-module enabled);
 * "address_country" (with "address" module and "geocoder_address" sub-module enabled);
 
###### for Reverse Geocode operations:
 
 * "geofield" (with "geofield" module and "geocoder_geofield" sub-module enabled);

**Note:** Geocoder Field sub-module provides hooks to alter (change and extend) the list of Geocoding and Reverse Geocoding fields types
(@see geocoder_field.api)

####Using Geocoder operations behind Proxy 

GeocoderHttpAdapter is based on the Drupal 8 Guzzle implementation, 
that is using settings array namespaced under $settings['http_client_config'].
Geocoding behind a proxy will be correctly set by (@see default.settings.php):

$settings['http_client_config']['proxy'];

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
$plugins = ['geonames', 'googlemaps', 'bingmaps'];
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

// Array of (ovverriding) options (@see Note* below)
$options = [
  'freegeoip' => [], // array of options
  'geonames' => [], // array of options
  'googlemaps' => [], // array of options
  'bingmaps' => [], // array of options
];

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins, $options);

Note*: The last $options array parameter is optional, and merges/overrides the default plugins options set in the module configurations, that will be used normally as defaults.

```

####Note

## Reverse geocode coordinates

```php
$plugins = ['freegeoip', 'geonames', 'googlemaps', 'bingmaps'];
$lat = '37.422782';
$lon = '-122.085099';

// Array of (ovverriding) options (@see Note* below)
$options = [
  'freegeoip' => [], // array of options
  'geonames' => [], // array of options
  'googlemaps' => [], // array of options
  'bingmaps' => [], // array of options
];

$addressCollection = \Drupal::service('geocoder')->reverse($lat, $lon, $plugins, $options);

Note*: The last $options array parameter is optional, and merges/overrides the default plugins options set in the module configurations, that will be used normally as defaults.

```

## Return format

Both ```Geocoder::geocode()``` and ```Geocoder::reverse()```
return the same object: ```Geocoder\Model\AddressCollection```,
which is itself composed of ```Geocoder\Model\Address```.

You can transform those objects into arrays. Example:

```php
$plugins = ['geonames', 'googlemaps', 'bingmaps'];
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins);
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

Here's an example on how to use a Dumper:

```php
$plugins = ['geonames', 'googlemaps', 'bingmaps']; 
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins);
$geojson = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geojson')->dump($addressCollection->first());
```

There's also a dumper for GeoPHP, here's how to use it:

```php
$plugins = ['geonames', 'googlemaps', 'bingmaps'];
$address = '1600 Amphitheatre Parkway Mountain View, CA 94043';

$addressCollection = \Drupal::service('geocoder')->geocode($address, $plugins);
$geometry = \Drupal::service('plugin.manager.geocoder.dumper')->createInstance('geometry')->dump($addressCollection->first());
```

##Geocoder API Url Endpoints 

The Geocoder module provides the following API Url endpoints (with Json output),
to consume for performing Geocode and Reverse Geocode operations respectively.

- #### Geocode
  This endpoint allows to process a Geocode operation 
  (get Geo Coordinates from Addresses) on the basis of an input Address, 
  the operational Geocoders and an (optional) output Format (Dumper).
  
  Path: **'/geocoder/api/geocode'**  
  Method: **GET**  
  Access Permission: **'access geocoder api endpoints'**  
  Successful Response Body Format: **json**  

  #####Query Parameters:
  
  - **address** (required): The Address string to geocode (the more detailed and 
  extended the better possible results.
  
  - **geocoder** (required): The Geocoder id, or a list of geocoders id separated by a comma 
  (,) that should process the request (in order of priority). At least one 
  should be provided. Each id should correspond with a valid @GeocoderProvider 
  plugin id.
   
    Note: (if not differently specified in the "options") the Geocoder 
    configurations ('/admin/config/system/geocoder') will be used for each 
    Geocoder geocoding/reverse geocoding.
   
  - **format** (optional): The geocoding output format id for each result. 
  It should be a single value, corresponding to one of the Dumper 
  (@GeocoderDumper) plugin id defined in the Geocoder module. Default value (or 
  fallback in case of not existing id): the output format of the specific 
  @GeocoderProvider plugin able to process the Geocode operation.
  
  - **options** (optional): Possible overriding geocoders options written 
  in the form of multi-dimensional arrays query-string (such as a[b][c]=d). 
  For instance to override the google maps locale parameter (into italian):
  
    ````options[googlemaps][locale]=it````
  
- #### Reverse Geocode
  This endpoint allows to process a Reverse Geocode operation (get an Address 
  from Geo Coordinates) on the basis of an input string of Latitude and 
  Longitude coordinates, the operational Geocoders and an (optional) 
  output Format (Dumper).
  
  Path: **'/geocoder/api/reverse_geocode'**  
  Method: **GET**  
  Access Permission: **'access geocoder api endpoints'**  
  Successful Response Body Format: **json**  
  
  #####Query Parameters:
  
  - **latlon** (required): The latitude and longitude values, in decimal 
  degrees, as string couple separated by a comma (,) specifying the location for
   which you wish to obtain the closest, human-readable address.
  
  - **geocoders** (required): *@see the Geocode endpoint parameters description*
   
  - **format** (optional): *@see the Geocode endpoint parameters description*
  
  - **options** (optional): *@see the Geocode endpoint parameters 
  description*
    
#### Successful and Unsuccessful Responses

If the Geocode or Reverse Geocode operation is successful each Response result
is a Json format output (array list of Json objects), with a 200 ("OK") 
response status code. 
Each result format will comply with the chosen output format (dumper).
It will be possible to retrieve the PHP results array with the Response 
getContent() method:

````
$response_array = JSON::decode($this->response->getContent());
$first_result = $response_array[0];
````

If something goes wrong in the Geocode or Reverse Geocode operations 
(no Geocoder provided, bad Geocoder configuration, etc.) the Response result 
output is empty, with a 204 ("No content") response status code. See the Drupal 
logs for information regarding possible Geocoders wrong configurations causes.


# Links
* [Composer](https://getcomposer.org/)
* [Drush](http://drush.org)
* [Geocoder PHP library](http://geocoder-php.org) - [version
  3.x](https://github.com/geocoder-php/Geocoder/tree/3.x)
* [Geocoder module](https://www.drupal.org/project/geocoder)
