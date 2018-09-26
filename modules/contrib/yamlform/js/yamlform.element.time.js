/**
 * @file
 * Javascript behaviors for time integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attach datepicker fallback on time elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to time elements.
   */
  Drupal.behaviors.yamlFormTime = {
    attach: function (context, settings) {
      var $context = $(context);
      // Skip if time inputs are supported by the browser.
      if (Modernizr.inputtypes.time === true) {
        return;
      }
      $context.find('input[type="time"]').once('timePicker').each(function () {
        var $input = $(this);

        var options = {};
        if ($input.data('yamlformTimeFormat')) {
          options.timeFormat = $input.data('yamlformTimeFormat');
        }
        if ($input.attr('min')) {
          options.minTime = $input.attr('min');
        }
        if ($input.attr('max')) {
          options.maxTime = $input.attr('max');
        }
        if ($input.attr('step')) {
          options.step = Math.round($input.attr('step') / 60);
        }

        $input.timepicker(options);
      });
    }
  }

})(jQuery, Drupal);
