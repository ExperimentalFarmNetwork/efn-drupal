(function ($) {

Drupal.behaviors.auto_nodetitleFieldsetSummaries = {
  attach: function (context) {
    $('details#edit-auto-nodetitle', context).drupalSetSummary(function (context) {
      // Retrieve the value of the selected radio button
      var ant = $(".form-item-auto-nodetitle-status input:checked").val();
      if (ant==0) {
        return Drupal.t('Disabled')
      }
      else if (ant==1) {
        return Drupal.t('Automatic (hide title field)')
      }
      else if (ant==2) {
        return Drupal.t('Automatic (if title empty)')
      }
    });
  }
};

})(jQuery);
