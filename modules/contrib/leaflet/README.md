###General Information

**Leaflet** module provides integration with 
[Leaflet map scripting library](http://leafletjs.com).
            
It is based and dependant from:
- the [Leaflet JS library](http://leafletjs.com); 
- the [Geofield](https://www.drupal.org/project/geofield) Module;

###Installation and Use

1. __Require/Download the Leaflet module
[using Composer to manage Drupal site dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)__,
which will also download the required 
[Geofield Module](https://www.drupal.org/project/geofield) 
dependency and GeoPHP library.  
It is done simply running the following command from your project package root 
(where the main composer.json file is sited):  
__$ composer require 'drupal/leaflet'__  
(for dev: __$ composer require 'drupal/leaflet:1.x-dev'__)
  
2. Enable the module to be able to use the configurable __Leaflet Map as 
Geofield Formatter__;

3. Enable "Leaflet Views" (leaflet_views) submodule for Leaflet Map Views 
integration.
You need to add at least one geofield to the Fields list, and select the Leaflet
Map style in the Display Format. 
In the settings of the style, select the geofield as the Data Source and select
a field for Title and Description (which will be rendered in the popup).

As a more powerful alternative, you can use node view modes to be rendered in
the popup. In the Description field, select "<entire node>" and then select a
View mode.

###API Usage

Rendering a map is as simple as instantiating the LeafletService and its 
leafletRenderMap method 
```
\Drupal::service('leaflet.service')->leafletRenderMap($map, $features, $height)
```
which takes 3 parameters:

* $map:
An associative array defining a map. See hook_leaflet_map_info(). The module
defines a default map with a OpenStreet Maps base layer.

* $features:
This is an associative array of all the Leaflet features you
want to plot on the map. A feature can be a point, linestring, polygon,
multipolygon, multipolygon, or json object. Additionally, features can be
grouped into [leaflet layer groups](http://leafletjs.com/reference-1.3.0.html#layergroup)
so they can be controlled together,

* $height:
The map height, expressed in css units.

Roadmap
-------

* Better UI for managing maps
* Move static & procedural API functions into Leaflet Services
* Purge of not working / useless leaflet_views views classes
* Better documentation

Authors/Credits
---------------

* [levelos](http://drupal.org/user/54135)
* [pvhee](http://drupal.org/user/108811)
* [itamair](https://www.drupal.org/u/itamair)
