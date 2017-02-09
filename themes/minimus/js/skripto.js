(function($, Drupal) {
    // change bootstrap button for add project update
    $(".block-group-operations button.btn-xs").removeClass('btn-xs btn-default').addClass('btn-lg btn-success');

    // footer buttons
    $("#block-donate li a").addClass('btn-lg btn-success');
    $("#block-footerblock1 a").addClass('btn-lg btn-success');

    // resize navbar on scroll
  //   var navbar=$('header.navbar-default');
  //   $(window).scroll(function () {
  //       if ($(window).scrollTop() > 160) {
  //         navbar.addClass('navbar-fixed-top');
  //     }
  //     if ($(window).scrollTop() < 161) {
  //         navbar.removeClass('navbar-fixed-top');
  //     }
  // });

// Toggle hidden fields on project page
    $("button.toggleVerbose").click(function(event) {
        $('.path-group .region-content .field').toggleClass('toggleVFields','1000');
        $(this).find('.glyphicon').toggleClass('glyphicon-collapse-up');
    });

Drupal.behaviors.commentThing = {
  attach: function (context, settings) {
    $(".views-field-field-discussion textarea.form-textarea, #node-project-update-field-discussion textarea.form-textarea, [id^=node-project-update-field-discussion] textarea.form-textarea").attr("placeholder", "Comment here ...").click(function(event) {
        $(this).attr('placeholder', '!');
        $('.js-form-submit').css('display', 'block');
        });
    }
}
    // Put header image in background of group pages

    if (($("body").hasClass('path-group')) && $('.field--name-field-project-image')){
        var headerImg = $(".path-group .field--name-field-project-image img").attr('src');
        $(".group-header").css({
            'background': 'url('+headerImg+')',
            'background-size':' cover',
            'background-repeat':'no-repeat'
        });

    } 
    if (! $('.field--name-field-project-image').length){
        $(".group-header").remove();
        console.log("gone");
    }

    // front page image to background of card
    if ($("body").hasClass('path-frontpage')){
        $(".view-id-project .views-row").each(function(index, el) {
        var cardPic = $(this).find(".views-field-field-project-image img").attr('src');

        $(this).css({
            background: 'url('+cardPic+')',
            'background-repeat': 'no-repeat',
            'background-color': '#fff' ,
            'background-size' : '100%'
        });
        });
    }
    $("[data-drupal-link-system-path='user']").before('<a href="/user" class="glyphicon glyphicon-user"></a>');
    $("[data-drupal-link-system-path='user/logout']").before('<a href="/user" class="glyphicon glyphicon-log-out"></a>');




})(jQuery, Drupal);