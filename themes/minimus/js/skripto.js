(function($, Drupal) {
    // change bootstrap button for add project update
    $(".block-group-operations button.btn-xs").removeClass('btn-xs btn-default').addClass('btn-lg btn-success');

    // resize navbar on scroll
    var navbar=$('header.navbar-default');
        $(window).scroll(function () {
        if ($(window).scrollTop() > 160) {
          navbar.addClass('navbar-fixed-top');
        }
        if ($(window).scrollTop() < 161) {
          navbar.removeClass('navbar-fixed-top');
        }
      });

    $("button.toggleVerbose").click(function(event) {
        $('.path-group .region-content .field').toggleClass('toggleVFields','1000');
    });

    $("textarea.form-textarea").attr("placeholder", "Comment here ...").click(function(event) {
        $(this).attr('placeholder', 'yes do it');
        $('.js-form-submit').css('display', 'block');
    });

    // Put header image in background of group pages
    var headerImg = $(".path-group .field--name-field-project-image img").attr('src');
    $("body.path-group").css({
        'background': 'url('+headerImg+')',
        'background-size':' contain',
        'background-repeat':'no-repeat'
    });

    // set project update inititial background offset
    var hdrHt=$("body.path-group .navbar-default").height(),
        hdrMargin=hdrHt+100;
    $("body.path-group").css('background-position-y', hdrMargin+'px');
    console.log(hdrMargin);

})(jQuery, Drupal);
