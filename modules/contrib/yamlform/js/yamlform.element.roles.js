/**
 * @file
 * Javascript behaviors for roles element integration.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Enhance roles element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.yamlFormRoles = {
    attach: function (context) {
      $(context).find('.js-yamlform-roles-role[value="authenticated"]').once('yamlform-roles').each(function () {
        var $authenticated = $(this);
        var $checkboxes = $authenticated.parents('.form-checkboxes').find('.js-yamlform-roles-role').filter(function() {
          return ($(this).val() != 'anonymous' && $(this).val() != 'authenticated');
        });

        $authenticated.on('click', function() {
          if ($authenticated.is(':checked')) {
            $checkboxes.prop('checked', true).attr('disabled', true);
          }
          else {
            $checkboxes.prop('checked', false).removeAttr('disabled');
          }
        });

        if ($authenticated.is(':checked')) {
          $checkboxes.prop('checked', true).attr('disabled', true);
        }
      })
    }
  };

})(jQuery, Drupal);
