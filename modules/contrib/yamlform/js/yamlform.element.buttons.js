/**
 * @file
 * Javascript behaviors for jQuery UI buttons element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Create jQuery UI buttons element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.yamlFormButtons = {
    attach: function (context) {
      $(context).find('fieldset.js-yamlform-buttons div.fieldset-wrapper').once('yamlform-buttons').each(function() {
        // Remove all div and classes around radios and labels.
        $(this).html($(this).find('input[type="radio"], label').removeClass());
        // Create buttonset.
        $(this).buttonset();
      });
    }
  };

})(jQuery, Drupal);
