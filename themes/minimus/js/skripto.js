(function($, Drupal) {
    // change bootstrap button for add project update
    $(".block-group-operations button.btn-xs").removeClass('btn-xs btn-default').addClass('btn-lg btn-success');

    // footer buttons
    $("#block-donate li a").addClass('btn-lg btn-success');
    $('#block-sociallinks a[href="/contact"').addClass('btn-lg btn-success');

    // Toggle hidden fields on project page
    var hEl = $(".field--name-field-project-image, .field--name-field-description,.field--name-field-researcher-background,.field--name-field-seeking-volunteers,.field--name-field-volunteers-how-many,.field--name-field-volunteers-ask-do,.field--name-field-volunteers-other-reqs,.field--name-field-multiyear,.field--name-field-volunteers-keep-seed,.field--name-field-privacy,.field--name-field-misc,.field--name-field-location,.field--name-field-location-geo");
    $(".toggleVerbose").click(function(event) {
        hEl.slideToggle('slow');
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
    }

    // front page image to background of card
    if ($("body").hasClass('path-frontpage')){
        $(".view-id-project .views-row").each(function(index, el) {
        var cardPic = $(this).find(".views-field-field-project-image img").attr('src');
        });
    }
    $("[data-drupal-link-system-path='user']").before('<a href="/user" class="glyphicon glyphicon-user"></a>');
    $("[data-drupal-link-system-path='user/logout']").before('<a href="/user" class="glyphicon glyphicon-log-out"></a>');

    // Volunteer Profile other checkbox
    $("input#edit-field-growing-experience-other").click(function(event) {
        /* Act on the event */
        if($(this).is(":checked")){
            $("#edit-field-other-wrapper").css('display', 'block');
        }else{
            $("#edit-field-other-wrapper").css('display', 'none');
        }
    });

})(jQuery, Drupal);