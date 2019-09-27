(function($, Drupal, drupalSettings) {

  Drupal.Leaflet.prototype.query_url_serialize = function(obj, prefix) {
    var str = [], p;
    for (p in obj) {
      if (obj.hasOwnProperty(p)) {
        var k = prefix ? prefix + "[" + p + "]" : p,
          v = obj[p];
        str.push((v !== null && typeof v === "object") ?
          Drupal.Leaflet.prototype.query_url_serialize(v, k) :
          encodeURIComponent(k) + "=" + encodeURIComponent(v));
      }
    }
    return str.join("&");
  };

  Drupal.Leaflet.prototype.geocode = function(address, providers, options) {
    var base_url = drupalSettings.path.baseUrl;
    var geocode_path = base_url + 'geocoder/api/geocode';
    options = Drupal.Leaflet.prototype.query_url_serialize(options);
    return $.ajax({
      url: geocode_path + '?address=' +  encodeURIComponent(address) + '&geocoder=' + providers + '&' + options,
      type:"GET",
      contentType:"application/json; charset=utf-8",
      dataType: "json",
    });
  };

  Drupal.Leaflet.prototype.map_geocoder_control = function(controlDiv, mapid) {
    var geocoder_settings = drupalSettings.leaflet[mapid].map.settings.geocoder.settings;
    var control = new L.Control({position: geocoder_settings.position});
    control.onAdd = function() {
      var controlUI = L.DomUtil.create('div','geocoder');
      controlUI.id = mapid + '--geocoder-control--container'
      controlDiv.appendChild(controlUI);

      // Set CSS for the control search interior.
      var controlSearch = document.createElement('input');
      controlSearch.placeholder = Drupal.t('Search Address');
      controlSearch.id = mapid + '--geocoder-control';
      controlSearch.title = Drupal.t('Search an Address on the Map');
      controlSearch.style.color = 'rgb(25,25,25)';
      controlSearch.style.padding = '0.2em 1em';
      controlSearch.style.borderRadius = '3px';
      controlSearch.size = geocoder_settings.input_size || 25;
      controlSearch.maxlength = 256;
      controlSearch.style.borderRadius = '3px';
      controlUI.appendChild(controlSearch);
      return controlUI;
    };

    return control;
  };

  Drupal.Leaflet.prototype.map_geocoder_control.autocomplete = function(mapid, geocoder_settings) {
    var providers = geocoder_settings.providers.toString();
    var options = geocoder_settings.options;
    $('#' + mapid + '--geocoder-control').autocomplete({
      // @todo Set a dynamic params.geocoder_min_terms
      autoFocus: true,
      minLength: geocoder_settings.min_terms || 4,
      delay: geocoder_settings.delay || 800,
      // This bit uses the geocoder to fetch address values.
      source: function (request, response) {
        // Execute the geocoder.
        $.when(Drupal.Leaflet.prototype.geocode(request.term, providers, options).then(
          // On Resolve/Success.
          function (results) {
            response($.map(results, function (item) {
              return {
                // the value property is needed to be passed to the select.
                value: item.formatted_address,
                lat: item.geometry.location.lat,
                lng: item.geometry.location.lng
              };
            }));
          },
          // On Reject/Error.
          function() {
            response(function(){
              return false;
            });
          }));
      },
      // This bit is executed upon selection of an address.
      select: function (event, ui) {
        var map = Drupal.Leaflet[mapid].lMap;
        var zoom = geocoder_settings.zoom || 14;
        var position = L.latLng(ui.item.lat, ui.item.lng);
        map.setView(position, zoom);
        if (geocoder_settings.popup) {
          L.popup().setLatLng(position)
            .setContent('<div class="leaflet-geocoder-popup">' + ui.item.value + '</div>')
            .openOn(map);
        }
      }
    });
  }

})(jQuery, Drupal, drupalSettings);
