(function ($, Drupal, once) {
  Drupal.behaviors.share_tabs = {
    attach: function (context, settings) {
      $('.share-tab .tablink', context).click(function() {
        console.log('clicked');
        var $this = $(this);
        var key = $(this).attr('data-tab-key');
        var $share_tabs = $this.closest('.share-tab');
        $('.tab-content', $share_tabs).hide();
        $('.tab-content[data-tab-key="' + key + '"]', $share_tabs).show();
      });
    },
  };
})(jQuery, Drupal, once);