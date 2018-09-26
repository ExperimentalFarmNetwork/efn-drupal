/**
 * @file
 * Javascript behaviors for custom form #states.
 */

(function ($, Drupal) {

  'use strict';

  // Make absolutely sure the below event handlers are triggered after
  // the state.js event handlers by attaching them after DOM load.
  $(function () {
    var $document = $(document);
    $document.on('state:visible', function (e) {
      if (!e.trigger) {
        return TRUE;
      }

      if (!e.value) {
        // @see https://www.sitepoint.com/jquery-function-clear-form-data/
        $(':input', e.target).andSelf().each(function () {
          var $input = $(this);
          backupValue(this);
          clearValue(this);
          triggerEventHandlers(this);
        });
      }
      else {
        $(':input', e.target).andSelf().each(function () {
          restoreValue(this);
          triggerEventHandlers(this);
        });
      }
    });

    $document.on('state:disabled', function (e) {
      if (e.trigger) {
        $(e.target).trigger('yamlform:disabled')
          .find('select, input, textarea').trigger('yamlform:disabled');
      }
    });
  });

  /**
   * Trigger an input's event handlers.
   *
   * @param input
   *   An input.
   */
  function triggerEventHandlers(input) {
    var $input = $(input);
    var type = input.type;
    var tag = input.tagName.toLowerCase(); // normalize case
    if (type == 'checkbox' || type == 'radio') {
      $input
        .trigger('change')
        .trigger('blur');
    }
    else if (tag == 'select') {
      $input
        .trigger('change')
        .trigger('blur');
    }
    else if (type != 'submit' && type != 'button') {
      $input
        .trigger('input')
        .trigger('change')
        .trigger('keydown')
        .trigger('keyup')
        .trigger('blur');
    }
  }

  /**
   * Backup an input's current value.
   *
   * @param input
   *   An input.
   */
  function backupValue(input) {
    var $input = $(input);
    var type = input.type;
    var tag = input.tagName.toLowerCase(); // normalize case
    if (type == 'checkbox' || type == 'radio') {
      $input.data('yamlform-value', $input.prop('checked'));
    }
    else if (tag == 'select') {
      var values = [];
      $input.find('option:selected').each(function(i, option){
        values[i] = option.value;
      });
      $input.data('yamlform-value', values);
    }
    else if (type != 'submit' && type != 'button') {
      $input.data('yamlform-value', input.value);
    }
  }

  /**
   * Restore an input's value.
   *
   * @param input
   *   An input.
   */
  function restoreValue(input) {
    var $input = $(input);
    var value = $input.data('yamlform-value');
    if (typeof value === 'undefined') {
      return true;
    }

    var type = input.type;
    var tag = input.tagName.toLowerCase(); // normalize case

    if (type == 'checkbox' || type == 'radio') {
      $input.prop('checked', value)
    }
    else if (tag == 'select') {
      $.each(value, function(i, option_value){
        $input.find("option[value='" + option_value + "']").prop("selected", true);
      });
    }
    else if (type != 'submit' && type != 'button') {
      input.value = value;
    }
  }

  /**
   * Clear an input's value.
   *
   * @param input
   *   An input.
   */
  function clearValue(input) {
    var $input = $(input);
    var type = input.type;
    var tag = input.tagName.toLowerCase(); // normalize case
    if (type == 'checkbox' || type == 'radio') {
      $input.prop('checked', false)
    }
    else if (tag == 'select') {
      if ($input.find('option[value=""]').length) {
        $input.val('');
      }
      else {
        input.selectedIndex = -1;
      }
    }
    else if (type != 'submit' && type != 'button') {
      input.value = (type == 'color') ? '#000000' : '';
    }
  }

})(jQuery, Drupal);
