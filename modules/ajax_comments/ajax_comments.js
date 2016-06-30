(function ($, window, Drupal, drupalSettings) {

  "use strict";

 // Scroll to given element
  Drupal.AjaxCommands.prototype.ajaxCommentsScrollToElement = function(ajax, response, status) {
    try {
      var pos = $(response.selector).offset();
      $('html, body').animate({ scrollTop: pos.top}, 'slow');
    }
    catch (e) {
      console.log('ajaxComments-ScrollToElementError: ' + e.name);
    }
  };

  /**
   * Add the dummy div if they are not exist.
   * On the server side we have a current state of node and comments, but on client side we may have a outdated state
   * and some div's may be not present
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsAddDummyDivAfter = function(ajax, response, status) {
    try {
      if (!$(response.selector).next().hasClass(response.class)) {
        $('<div class="' + response.class + '"></div>').insertAfter(response.selector);
      }
    }
    catch (e) {
      console.log('ajaxComments-AddDummyDivAfter: ' + e.name);
    }
  };

  /*
   * These function may be removed when bug #736066 is fixed
   * At this time, ajax.js automatically wrap comment content into div when we use ajax_command_NAME functions,
   * and this is not good for us because this broke html layout
   */

  /**
   * Own implementation of ajax_command_replace()
   * see bug: https://www.drupal.org/node/736066
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsReplace = function(ajax, response, status) {
    try {
      // Removing content from the wrapper, detach behaviors first.
      var wrapper = response.selector ? $(response.selector) : $(ajax.wrapper);
      var settings = response.settings || ajax.settings || Drupal.settings;
      Drupal.detachBehaviors(wrapper, settings);

      $(response.selector).replaceWith(response.html);

      // Attach all JavaScript behaviors to the new content, if it was successfully
      // added to the page, this if statement allows #ajax['wrapper'] to be
      // optional.
      var settings = response.settings || ajax.settings || Drupal.settings;
      Drupal.attachBehaviors(response.data, settings);
    }
    catch (e) {
      console.log('ajaxComments-Replace: ' + e.name)
    }
  };

  /**
   * Own implementation of ajax_command_before()
   * see bug: https://www.drupal.org/node/736066
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsBefore = function(ajax, response, status) {
    try {
      $(response.html).insertBefore(response.selector);

      // Attach all JavaScript behaviors to the new content, if it was successfully
      // added to the page, this if statement allows #ajax['wrapper'] to be
      // optional.
      var settings = response.settings || ajax.settings || Drupal.settings;
        Drupal.attachBehaviors(response.data, settings);
      }
      catch (e) {
        console.log('ajaxComments-Before: ' + e.name)
      }
  };

  /**
   * Own implementation of ajax_command_after()
   * see bug: https://www.drupal.org/node/736066
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsAfter = function(ajax, response, status) {
    try {
      $(response.html).insertAfter(response.selector);

      // Attach all JavaScript behaviors to the new content, if it was successfully
      // added to the page, this if statement allows #ajax['wrapper'] to be
      // optional.
      var settings = response.settings || ajax.settings || Drupal.settings;
      Drupal.attachBehaviors(response.data, settings);
    }
    catch (e) {
      console.log('ajaxComments-After: ' + e.name)
    }
  };

  /**
   * Own implementation of ajax_command_insert()
   * see bug: https://www.drupal.org/node/736066
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsPrepend = function(ajax, response, status) {
    try {
      $(response.selector).prepend(response.html);

      // Attach all JavaScript behaviors to the new content, if it was successfully
      // added to the page, this if statement allows #ajax['wrapper'] to be
      // optional.
      var settings = response.settings || ajax.settings || Drupal.settings;
      Drupal.attachBehaviors(response.data, settings);
    }
    catch (e) {
      console.log('ajaxComments-Prepend: ' + e.name)
    }
  };

  /**
   * Own implementation of ajax_command_append()
   * see bug: https://www.drupal.org/node/736066
   */
  Drupal.AjaxCommands.prototype.ajaxCommentsAppend = function(ajax, response, status) {
    try {
      $(response.selector).append(response.html);

      // Attach all JavaScript behaviors to the new content, if it was successfully
      // added to the page, this if statement allows #ajax['wrapper'] to be
      // optional.
      var settings = response.settings || ajax.settings || Drupal.settings;
      Drupal.attachBehaviors(response.data, settings);
    }
    catch (e) {
      console.log('ajaxComments-Append: ' + e.name)
    }
  };

  /**
   * Own Bind Ajax behavior for comment links.
   */
  Drupal.behaviors.ajaxCommentsBehavior = {
    attach: function(context, settings) {
      // Bind Ajax behavior to all items showing the class.
      $('.js-use-ajax-comments', context).once('ajax-comments').each(function () {
        $(this).click(function (e) {
          e.preventDefault();
        });
      });
    },

    /**
     * Scan a dialog for any button-style links and move them to the button area.
     *
     * @param {Drupal.dialog~dialogDefinition} dialog
     *   The Drupal.dialog object.
     * @param {jQuery} $element
     *   An jQuery object containing the element that is the dialog target.
     * @param {object} settings
     *   The dialog settings object.
     */
    prepareDialogButtons: function (dialog, $element, settings) {
      var buttons = settings.buttons || [];
      var $buttonLinks = $element.find('a.button');
      $buttonLinks.once('ajax-comments').each(function () {
        var $originalButton = $(this).css({
          display: 'none',
          visibility: 'hidden'
        });
        buttons.push({
          text: $originalButton.html(),
          class: $originalButton.attr('class'),
          click: function(e) {
            $originalButton.trigger('click');
            e.preventDefault();
            e.stopPropagation();
          }
        });
      });
      $element.dialog('option', 'buttons', buttons);
    }

  };

  /**
   * Override and extend the functionality of Drupal.Ajax.prototype.beforeSerialize.
   */
  (function (beforeSerialize) {
    Drupal.Ajax.prototype.beforeSerialize = function (element, options) {
      beforeSerialize.call(this, element, options);
      var wrapperHtmlId = $(element).data('wrapper-html-id') || null;
      if (wrapperHtmlId) {
        options.data['wrapper_html_id'] = wrapperHtmlId;
      }
    };
  })(Drupal.Ajax.prototype.beforeSerialize);


  /**
   * Binds a listener on dialog creation to handle dialog customizations.
   *
   * @param {jQuery.Event} e
   * @param {Drupal.dialog~dialogDefinition} dialog
   * @param {jQuery} $element
   * @param {object} settings
   */
  $(window).on('dialog:aftercreate', function (e, dialog, $element, settings) {
    // Only apply this logic on Ajax Comments forms
    if ($element.find('form.ajax-comments').length) {
      Drupal.behaviors.ajaxCommentsBehavior.prepareDialogButtons(dialog, $element, settings);
    }
  });

})(jQuery, this, Drupal, drupalSettings);
