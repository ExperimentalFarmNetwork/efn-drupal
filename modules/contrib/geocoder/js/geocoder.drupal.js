(function($, Drupal, drupalSettings) {

  Drupal.behaviors.geocode_origin_autocomplete = {
    attach: function(context, settings) {

      function query_url_serialize(obj, prefix) {
        var str = [], p;
        for (p in obj) {
          if (obj.hasOwnProperty(p)) {
            var k = prefix ? prefix + "[" + p + "]" : p,
              v = obj[p];
            str.push((v !== null && typeof v === "object") ?
              Drupal.geoFieldMap.query_url_serialize(v, k) :
              encodeURIComponent(k) + "=" + encodeURIComponent(v));
          }
        }
        return str.join("&");
      };

      function geocode (address, providers, options, address_format) {
        var base_url = drupalSettings.path.baseUrl;
        var geocode_path = base_url + 'geocoder/api/geocode';
        var address_format_query_url = address_format === null ? '' : '&address_format=' + address_format;
        options = '&' + query_url_serialize(options);
        return $.ajax({
          url: geocode_path + '?address=' +  encodeURIComponent(address) + '&geocoder=' + providers + options + address_format_query_url,
          type:"GET",
          contentType:"application/json; charset=utf-8",
          dataType: "json",
        });
      }

      // Run filters on page load if state is saved by browser.
      $('.origin-address-autocomplete .address-input', context).once('autocomplete-enabled').each(function () {
        var providers = settings.geocode_origin_autocomplete.providers.toString();
        var options = settings.geocode_origin_autocomplete.options;
        var address_format = settings.geocode_origin_autocomplete.address_format;
        $(this).autocomplete({
          autoFocus: true,
          minLength: settings.geocode_origin_autocomplete.minTerms || 4,
          delay: settings.geocode_origin_autocomplete.delay || 800,
          // This bit uses the geocoder to fetch address values.
          source: function (request, response) {
            // Execute the geocoder.
            $.when(geocode(request.term, providers, options, address_format).then(
              // On Resolve/Success.
              function (results) {
                response($.map(results, function (item) {
                  return {
                    // the value property is needed to be passed to the select.
                    value: item.formatted_address,
                  };
                }));
              },
              // On Reject/Error.
              function() {
                response(function(){
                  return false;
                });
              }));
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
