// geo-location shim
// Source: https://gist.github.com/paulirish/366184

// Currently only serves lat/long
// depends on jQuery

(function(geolocation, $){
  if (geolocation) return;

  var cache;

  geolocation = window.navigator.geolocation = {};
  geolocation.getCurrentPosition = function(callback){

    if (cache) callback(cache);

    $.getScript('//www.google.com/jsapi',function(){

      cache = {
        coords : {
          "latitude": google.loader.ClientLocation.latitude,
          "longitude": google.loader.ClientLocation.longitude
        }
      };

      callback(cache);
    });

  };

  geolocation.watchPosition = geolocation.getCurrentPosition;

})(navigator.geolocation, jQuery);

(function ($) {
  Drupal.behaviors.geofieldGeolocation = {
    attach: function (context, settings) {

      // Callback for getCurrentPosition on geofield widget html5 geocode button
      function updateLocation(position) {
        $fields.find('.auto-geocode .geofield-lat').val(position.coords.latitude);
        $fields.find('.auto-geocode .geofield-lon').val(position.coords.longitude);
      }

      // Callback for getCurrentPosition on geofield proximity client position.
      function getClientOrigin(position) {
        var lat = position.coords.latitude.toFixed(6);
        var lon = position.coords.longitude.toFixed(6);
        latitudeInput.val(lat);
        longitudeInput.val(lon);
        latitudeSpan.text(lat);
        longitudeSpan.text(lon);
        return false;
      }

      // don't do anything if we're on field configuration
      if (!$(context).find("#edit-instance").length) {
        var $fields = $(context);
        // check that we have something to fill up
        // on multi values check only that the first one is em  pty
        if ($fields.find('.auto-geocode .geofield-lat').val() === '' && $fields.find('.auto-geocode .geofield-lon').val() === '') {
          // Check to see if we have geolocation support, either natively or through Google.
          if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(updateLocation);
          }
          else {
            console.log('Geolocation is not supported by this browser.');
          }
        }
      }

      // React on the geofield widget html5 geocode button click.
      $('input[name="geofield-html5-geocode-button"]').once('geofield_geolocation').click(function (e) {
        e.preventDefault();
        $fields = $(this).parents('.auto-geocode').parent();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(updateLocation);
        }
        else {
          console.log('Geolocation is not supported by this browser.');
        }
      });

      var latitudeInput, longitudeInput, latitudeSpan, longitudeSpan = '';

      // React on the geofield proximity client location source.
      $('.proximity-origin-client').once('geofield_geolocation').each(function (e) {
        latitudeInput = $(this).find('.geofield-lat').first();
        longitudeInput = $(this).find('.geofield-lon').first();
        latitudeSpan = $(this).find('.geofield-lat-summary').first();
        longitudeSpan = $(this).find('.geofield-lon-summary').first();
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(getClientOrigin);
        }
        else {
          console.log('Geolocation is not supported by this browser.');
        }
      });
    }
  }

})(jQuery);
