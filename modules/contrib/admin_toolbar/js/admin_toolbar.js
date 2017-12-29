(function ($) {
  $(document).ready(function () {
    $('a.toolbar-icon').removeAttr('title');

    $('.toolbar-tray-horizontal li.menu-item--expanded, .toolbar-tray-horizontal ul li.menu-item--expanded .menu-item').hoverIntent({
      over: function () {
        // At the current depth, we should delete all "hover-intent" classes.
        // Other wise we get unwanted behaviour where menu items are expanded while already in hovering other ones.
        $(this).parent().find('li').removeClass('hover-intent');
        $(this).addClass('hover-intent');
      },
      out: function () {
        $(this).removeClass('hover-intent');
      },
      timeout: 250
    });

    // Make the toolbar menu navigable with keyboard.
    $('ul.toolbar-menu li.menu-item--expanded a').on('focusin', function () {
      $('li.menu-item--expanded').removeClass('hover-intent');
      $(this).parents('li.menu-item--expanded').addClass('hover-intent');
    });

    $('ul.toolbar-menu li.menu-item a').keydown(function (e) {
      if ((e.shiftKey && (e.keyCode || e.which) == 9)) {
        if ($(this).parent('.menu-item').prev().hasClass('menu-item--expanded')) {
          $(this).parent('.menu-item').prev().addClass('hover-intent');
        }
      }
    });

    $('.toolbar-menu:first-child > .menu-item:not(.menu-item--expanded) a, .toolbar-tab > a').on('focusin', function () {
      $('.menu-item--expanded').removeClass('hover-intent');
    });

    $('.toolbar-menu:first-child > .menu-item').on('hover', function () {
      $(this,'a').css("background: #fff;");
    });

    $('ul:not(.toolbar-menu)').on({
      mousemove: function () {
        $('li.menu-item--expanded').removeClass('hover-intent');
      },
      hover: function () {
        $('li.menu-item--expanded').removeClass('hover-intent');
      }
    });

  });
})(jQuery);
