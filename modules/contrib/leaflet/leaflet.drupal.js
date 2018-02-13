(function ($) {

  Drupal.behaviors.leaflet = {
    attach: function (context, settings) {

      $.each(settings.leaflet, function (m, data) {
        $('#' + data.mapId, context).each(function () {
          var $container = $(this);

          // If the attached context contains any leaflet maps, make sure we have a Drupal.leaflet_widget object.
          if ($container.data('leaflet') == undefined) {
            $container.data('leaflet', new Drupal.Leaflet(L.DomUtil.get(data.mapId), data.mapId, data.map));
            $container.data('leaflet').add_features(data.features, true);

            // Add the leaflet map to our settings object to make it accessible
            data.lMap = $container.data('leaflet').lMap;
          }
          else {
            // If we already had a map instance, add new features.
            // @todo Does this work? Needs testing.
            if (data.features != undefined) {
              $container.data('leaflet').add_features(data.features);
            }
          }
        });
        // Destroy features so that an AJAX reload does not get parts of the old set.
        // Required when the View has "Use AJAX" set to Yes.
        // @todo Is this still necessary? Needs testing.
        data.features = null;
      });
    }
  };

  Drupal.Leaflet = function (container, mapId, map_definition) {
    this.container = container;
    this.mapId = mapId;
    this.map_definition = map_definition;
    this.settings = this.map_definition.settings;
    this.bounds = [];
    this.base_layers = {};
    this.overlays = {};
    this.lMap = null;
    this.layer_control = null;

    this.initialise();
  };

  Drupal.Leaflet.prototype.initialise = function () {
    // Instantiate a new Leaflet map.
    this.lMap = new L.Map(this.mapId, this.settings);

    // Add map base layers.
    for (var key in this.map_definition.layers) {
      var layer = this.map_definition.layers[key];
      this.add_base_layer(key, layer);
    }

    // Set initial view, fallback to displaying the whole world.
    if (this.settings.center && this.settings.zoom) {
      this.lMap.setView(new L.LatLng(this.settings.center.lat, this.settings.center.lng), this.settings.zoom);
    }
    else {
      this.lMap.fitWorld();
    }

    // Add attribution
    if (this.settings.attributionControl && this.map_definition.attribution) {
      this.lMap.attributionControl.setPrefix(this.map_definition.attribution.prefix);
      this.attributionControl.addAttribution(this.map_definition.attribution.text);
    }

    // allow other modules to get access to the map object using jQuery's trigger method
    $(document).trigger('leaflet.map', [this.map_definition, this.lMap, this]);
  };

  Drupal.Leaflet.prototype.initialise_layer_control = function () {
    var count_layers = function (obj) {
      // Browser compatibility: Chrome, IE 9+, FF 4+, or Safari 5+
      // @see http://kangax.github.com/es5-compat-table/
      return Object.keys(obj).length;
    };

    // Only add a layer switcher if it is enabled in settings, and we have
    // at least two base layers or at least one overlay.
    if (this.layer_control == null && this.settings.layerControl && (count_layers(this.base_layers) > 1 || count_layers(this.overlays) > 0)) {
      // Only add base-layers if we have more than one, i.e. if there actually
      // is a choice for the user.
      var _layers = this.base_layers.length > 1 ? this.base_layers : [];
      // Instantiate layer control, using settings.layerControl as settings.
      this.layer_control = new L.Control.Layers(_layers, this.overlays, this.settings.layerControl);
      this.lMap.addControl(this.layer_control);
    }
  };

  Drupal.Leaflet.prototype.add_base_layer = function (key, definition) {
    var map_layer = this.create_layer(definition, key);
    this.base_layers[key] = map_layer;
    this.lMap.addLayer(map_layer);

    if (this.layer_control == null) {
      this.initialise_layer_control();
    }
    else {
      // If we already have a layer control, add the new base layer to it.
      this.layer_control.addBaseLayer(map_layer, key);
    }
  };

  Drupal.Leaflet.prototype.add_overlay = function (label, layer) {
    this.overlays[label] = layer;
    this.lMap.addLayer(layer);

    if (this.layer_control == null) {
      this.initialise_layer_control();
    }
    else {
      // If we already have a layer control, add the new overlay to it.
      this.layer_control.addOverlay(layer, label);
    }
  };

  Drupal.Leaflet.prototype.add_features = function (features, initial) {
    for (var i = 0; i < features.length; i++) {
      var feature = features[i];
      var lFeature;

      // dealing with a layer group
      if (feature.group) {
        var lGroup = this.create_feature_group(feature);
        for (var groupKey in feature.features) {
          var groupFeature = feature.features[groupKey];
          lFeature = this.create_feature(groupFeature);
          if (lFeature != undefined) {
            if (groupFeature.popup) {
              lFeature.bindPopup(groupFeature.popup);
            }
            lGroup.addLayer(lFeature);
          }
        }

        // Add the group to the layer switcher.
        this.add_overlay(feature.label, lGroup);
      }
      else {
        lFeature = this.create_feature(feature);
        if (lFeature != undefined) {
          this.lMap.addLayer(lFeature);

          if (feature.popup) {
            lFeature.bindPopup(feature.popup);
          }
        }
      }

      // Allow others to do something with the feature that was just added to the map
      $(document).trigger('leaflet.feature', [lFeature, feature, this]);
    }

    // Fit bounds after adding features.
    this.fitbounds();

    // Allow plugins to do things after features have been added.
    $(document).trigger('leaflet.features', [initial || false, this])
  };

  Drupal.Leaflet.prototype.create_feature_group = function (feature) {
    return new L.LayerGroup();
  };

  Drupal.Leaflet.prototype.create_feature = function (feature) {
    var lFeature;
    switch (feature.type) {
      case 'point':
        lFeature = this.create_point(feature);
        break;
      case 'linestring':
        lFeature = this.create_linestring(feature);
        break;
      case 'polygon':
        lFeature = this.create_polygon(feature);
        break;
      case 'multipolygon':
      case 'multipolyline':
        lFeature = this.create_multipoly(feature);
        break;
      case 'json':
        lFeature = this.create_json(feature.json);
        break;
      default:
        return; // Crash and burn.
    }

    // assign our given unique ID, useful for associating nodes
    if (feature.leaflet_id) {
      lFeature._leaflet_id = feature.leaflet_id;
    }

    var options = {};
    if (feature.options) {
      for (var option in feature.options) {
        options[option] = feature.options[option];
      }
      lFeature.setStyle(options);
    }

    return lFeature;
  };

  Drupal.Leaflet.prototype.create_layer = function (layer, key) {
    var map_layer = new L.TileLayer(layer.urlTemplate);
    map_layer._leaflet_id = key;

    if (layer.options) {
      for (var option in layer.options) {
        map_layer.options[option] = layer.options[option];
      }
    }

    // layers served from TileStream need this correction in the y coordinates
    // TODO: Need to explore this more and find a more elegant solution
    if (layer.type == 'tilestream') {
      map_layer.getTileUrl = function (tilePoint) {
        this._adjustTilePoint(tilePoint);
        var zoom = this._getZoomForUrl();
        return L.Util.template(this._url, L.Util.extend({
          s: this._getSubdomain(tilePoint),
          z: zoom,
          x: tilePoint.x,
          y: Math.pow(2, zoom) - tilePoint.y - 1
        }, this.options));
      }
    }
    return map_layer;
  };

  Drupal.Leaflet.prototype.create_icon = function (options) {
    var icon = new L.Icon({iconUrl: options.iconUrl});

    // override applicable marker defaults
    if (options.iconSize) {
      icon.options.iconSize = new L.Point(parseInt(options.iconSize.x), parseInt(options.iconSize.y));
    }
    if (options.iconAnchor) {
      icon.options.iconAnchor = new L.Point(parseFloat(options.iconAnchor.x), parseFloat(options.iconAnchor.y));
    }
    if (options.popupAnchor) {
      icon.options.popupAnchor = new L.Point(parseFloat(options.popupAnchor.x), parseFloat(options.popupAnchor.y));
    }
    if (options.shadowUrl !== undefined) {
      icon.options.shadowUrl = options.shadowUrl;
    }
    if (options.shadowSize) {
      icon.options.shadowSize = new L.Point(parseInt(options.shadowSize.x), parseInt(options.shadowSize.y));
    }
    if (options.shadowAnchor) {
      icon.options.shadowAnchor = new L.Point(parseInt(options.shadowAnchor.x), parseInt(options.shadowAnchor.y));
    }

    return icon;
  };

  Drupal.Leaflet.prototype.create_point = function (marker) {
    var latLng = new L.LatLng(marker.lat, marker.lon);
    this.bounds.push(latLng);
    var lMarker;

    if (marker.icon) {
      var icon = this.create_icon(marker.icon);
      lMarker = new L.Marker(latLng, {icon: icon});
    }
    else {
      lMarker = new L.Marker(latLng);
    }
    return lMarker;
  };

  Drupal.Leaflet.prototype.create_linestring = function (polyline) {
    var latlngs = [];
    for (var i = 0; i < polyline.points.length; i++) {
      var latlng = new L.LatLng(polyline.points[i].lat, polyline.points[i].lon);
      latlngs.push(latlng);
      this.bounds.push(latlng);
    }
    return new L.Polyline(latlngs);
  };

  Drupal.Leaflet.prototype.create_polygon = function (polygon) {
    var latlngs = [];
    for (var i = 0; i < polygon.points.length; i++) {
      var latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
      latlngs.push(latlng);
      this.bounds.push(latlng);
    }
    return new L.Polygon(latlngs);
  };

  Drupal.Leaflet.prototype.create_multipoly = function (multipoly) {
    var polygons = [];
    for (var x = 0; x < multipoly.component.length; x++) {
      var latlngs = [];
      var polygon = multipoly.component[x];
      for (var i = 0; i < polygon.points.length; i++) {
        var latlng = new L.LatLng(polygon.points[i].lat, polygon.points[i].lon);
        latlngs.push(latlng);
        this.bounds.push(latlng);
      }
      polygons.push(latlngs);
    }
    if (multipoly.multipolyline) {
      return new L.MultiPolyline(polygons);
    }
    else {
      return new L.MultiPolygon(polygons);
    }
  };

  Drupal.Leaflet.prototype.create_json = function (json) {
    lJSON = new L.GeoJSON();

    lJSON.on('featureparse', function (e) {
      e.layer.bindPopup(e.properties.popup);

      for (var layer_id in e.layer._layers) {
        for (var i in e.layer._layers[layer_id]._latlngs) {
          Drupal.Leaflet.bounds.push(e.layer._layers[layer_id]._latlngs[i]);
        }
      }

      if (e.properties.style) {
        e.layer.setStyle(e.properties.style);
      }

      if (e.properties.leaflet_id) {
        e.layer._leaflet_id = e.properties.leaflet_id;
      }
    });

    lJSON.addData(json);
    return lJSON;
  };

  Drupal.Leaflet.prototype.fitbounds = function () {
    if (this.bounds.length > 0) {
      this.lMap.fitBounds(new L.LatLngBounds(this.bounds));
    }
    // If we have provided a zoom level, then use it after fitting bounds.
    if (this.settings.zoom) {
      this.lMap.setZoom(this.settings.zoom);
    }
  };

})(jQuery);
