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
        var timeFormat = $input.data('yamlformTimeFormat');
        var options = {
          'timeFormat': timeFormat,
          'minTime': $input.attr('min') || null,
          'maxTime': $input.attr('max') || null,
          'step': ($input.attr('step')) ? Math.round($input.attr('step') / 60) : null
        };
        $input.timepicker(options);
      });
    }
  }

})(jQuery, Drupal);
