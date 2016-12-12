(function($, Drupal) {
    $(".block-group-operations button.btn-xs").removeClass('btn-xs btn-default').addClass('btn-lg btn-success');

// resize navbar on scroll
var navbar=$('header.navbar-default');
    $(window).scroll(function () {
      //if you hard code, then use console
      //.log to determine when you wa
 
      //nav bar to stick.  
      console.log($(window).scrollTop());
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
});;

})(jQuery, Drupal);
