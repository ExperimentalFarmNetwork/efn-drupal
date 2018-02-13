GEOFIELD for Drupal 8
--------------
[Geofield](http://drupal.org/project/geofield) is a Drupal 8 module that
provides a field types for storing geographic data. This data can be attached
to any entity, e.g., nodes, users and taxonomy terms. Geofield provides
different widgets for data input and formatters for data output. The Geofield
module can can store data as Latitude and Longitude, Bounding Box and Well
Known Text (WKT) and it supports all types of geographical data: points,
lines, polygons, multi-types etc.

###Install

Install the modules Geofield and geoPHP in the usual way. General information
on installing Drupal modules can be found here: http://drupal.
org/documentation/install/modules-themes/modules-7

__The Drupal 8 version of Geofield module module needs to be installed 
[using Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)__,
which will also download , which will also download the required GeoPHP library
dependency. It means simply running the following command from your project root
(where the main composer.json file is sited):

__$ composer require drupal/geofield__

and then enable the module in the usual way, from the extend/modules backend
interfaces or via drush with the following command:

__$ drush en geofield__


###Configure

Once enabled the module it will be possible to add a "Geofield" field type to 
any entity type/bundle and then choose the preferred widget or formatter.

###Mapping with Geofield

It is possible to implement advanced Mapping and Geocoding functionalities 
adding compatible and specialized modules for Drupal 8, such as:

- __[Geofield Map module for D8](https://www.drupal.org/project/geofield_map)__: an advanced, complete and easy-to-use Geo Mapping solution for
Drupal 8, based on Geofield

- __[Leaflet module for D8](https://www.drupal.org/project/leaflet)__: a moderately powerful mapping system based on the Leaflet JavaScript
 library 
 
 - __[Geocoder for D8](https://www.drupal.org/project/geocoder)__: Geocode string & text addresses or file-uploads into Geofield locations and viceversa (Reverse Geocode)
 

###Authors/Credits

Original author (Drupal 7):  
 [tristanoneil](https://www.drupal.org/user/340659)
 
Contributors for Drupal 8:    
[brandonian](https://www.drupal.org/u/brandonian)  
[plopesc](https://www.drupal.org/u/plopesc)  
[itamair](https://www.drupal.org/u/itamair)  

####Roadmap

* Re-implement the Views proximity filter/field for Drupal 8
* Test coverage verification and finalization

###Api Notes

Geofield fields contain nine columns of information about the geographic data
that is stores. At its heart is the 'wkt' column where it stores the full
geometry in the 'Well Known Text' (WKT) format. All other columns are metadata
derived from the WKT column. Columns are as follows:
```
  'wkt'          WKT
  'geo_type'     Type of geometry (point, linestring, polygon etc.)
  'lat'          Centroid (Latitude or Y)
  'lon'          Centroid (Longitude or X)
  'top'          Bounding Box Top (Latitude or Max Y)
  'bottom'       Bounding Box Bottom (Latitude or Min Y)
  'left'         Bounding Box Left (Longitude or Min X)
  'right'        Bounding Box Right (Longitude or Max X)
  ```
