/**
 * @file
 * Javascript behaviors for forms.
 */

(function ($, Drupal) {

  'use strict';

  var unsaved = false;

  /**
   * Unsaved changes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for unsaved changes.
   */
  Drupal.behaviors.yamlFormUnsaved = {
    attach: function (context) {
      // Look for the 'data-yamlform-unsaved' attribute which indicates that the
      // multi-step form has unsaved data.
      // @see \Drupal\yamlform\YamlFormSubmissionForm::buildForm
      if ($('.js-yamlform-unsaved[data-yamlform-unsaved]').length) {
        unsaved = true;
      }
      else {
        $('.js-yamlform-unsaved :input:not(input[type=\'submit\'])', context).once('yamlform-unsaved').on('change keypress', function(){
          unsaved = true;
        });
      }

      $('.js-yamlform-unsaved button, .js-yamlform-unsaved input[type=\'submit\']', context).once('yamlform-unsaved').on('click', function(){
        unsaved = false;
      });
    }
  };

  $(window).on('beforeunload', function() {
    if (unsaved) {
      return true;
    }
  });

  /*!
   * An experimental shim to partially emulate onBeforeUnload on iOS.
   * Part of https://github.com/codedance/jquery.AreYouSure/
   *
   * Copyright (c) 2012-2014, Chris Dance and PaperCut Software http://www.papercut.com/
   * Dual licensed under the MIT or GPL Version 2 licenses.
   * http://jquery.org/license
   *
   * Author:  chris.dance@papercut.com
   * Date:    19th May 2014
   */
  $(function() {
    if (!navigator.userAgent.toLowerCase().match(/iphone|ipad|ipod|opera/)) {
      return;
    }
    $('a').bind('click', function(evt) {
      var href = $(evt.target).closest('a').attr('href');
      if (href !== undefined && !(href.match(/^#/) || href.trim() == '')) {
        if ($(window).triggerHandler('beforeunload')) {
          if (!confirm(Drupal.t('Changes you made may not be saved.') + '\n\n' + Drupal.t('Press OK to leave this page or Cancel to stay.'))) {
            return false;
          }
        }
        window.location.href = href;
        return false;
      }
    });
  });

})(jQuery, Drupal);

