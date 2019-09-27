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

- __[Leaflet module for D8](https://www.drupal.org/project/leaflet)__: a moderately powerful mapping system based on the Leaflet JavaScript library 

- __[Leaflet Widget module for D8](https://www.drupal.org/project/leaflet_widget)__: a Geofield widget that uses the Leaflet Map for adding and removing geometries

- __[Geocoder for D8](https://www.drupal.org/project/geocoder)__: Geocode string & text addresses or file-uploads into Geofield locations and viceversa (Reverse Geocode)

 
 ###Api Notes
 
 #####Geofield Schema
 
 Geofield fields contain nine columns of information about the geographic data
 that is stores. At its heart is the 'wkt' column where it stores the full
 geometry in the 'Well Known Text' (WKT) format. All other columns are metadata
 derived from the WKT column. Columns are as follows:
 ```
   'wkt'          Raw value. By default, stored as WKB, loaded as WKT
   'geo_type'     Type of geometry (point, linestring, polygon etc.)
   'lat'          Centroid (Latitude or Y)
   'lon'          Centroid (Longitude or X)
   'top'          Bounding Box Top (Latitude or Max Y)
   'bottom'       Bounding Box Bottom (Latitude or Min Y)
   'left'         Bounding Box Left (Longitude or Min X)
   'right'        Bounding Box Right (Longitude or Max X)
   'geohash'      Geohash equivalent of geom column value
   ```
 #####Save or Updated a Geofield programmatically
 
 To save or update programatically a Geofield (both single and multivalue) it is sufficient to pass the WKT values/geometries to the
 
 {Drupal\geofield\Plugin\Field\FieldType\GeofieldItem} setValue public method
 
 For instance in case of a node entity containing a geofield named "field_geofield",
 it is possible to update/set its multiple values in the following way:
 
     // The location of the Empire State Building, in New York City (US)
     $empire_location_lon_lat = [-73.985664, 40.748441];
     
     // Generate the WKT version of the point geometry: 'POINT (-73.985664 41.748441)'
     $empire_location_wkt = \Drupal::service('geofield.wkt_generator')->wktBuildPoint($empire_location_lon_lat);
     
     // Generate the (first) geofield value in the proper format. 
     $geofield_point = [
     'value' => $empire_location_wkt,
     ];
     
     // Generate the (second) geofield value in the proper format. 
     // The permiter of Bryant Park, in New York City (US)
     $geofield_polygon = [
     'value' => 'POLYGON((-73.98411932014324 40.754779803566606,-73.98502054237224 40.75354445673964,-73.98186626457073 40.75221155678824,-73.98092212699748 40.75344692838096,-73.98411932014324 40.754779803566606))',
     ];
     
      // Get the wanted entity ($id of a node in this example) and set the 
      // 'field_geofield' with the goefield values/geometries
      $entity =  \Drupal\node\Entity\Node::load($id);
      $geofield = $entity->get('field_geofield');
      $geofield->setValue([$geofield_point, $geofield_polygon]);
      $entity->save();
      

###Authors/Credits

Original author (Drupal 7):  
 [tristanoneil](https://www.drupal.org/user/340659)
 
Contributors for Drupal 8:    
[brandonian](https://www.drupal.org/u/brandonian)  
[plopesc](https://www.drupal.org/u/plopesc)  
[itamair](https://www.drupal.org/u/itamair)  
