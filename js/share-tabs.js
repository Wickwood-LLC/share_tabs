(function ($, Drupal, once) {
  function clickShareTab($tab) {
    // console.log('clicked');
    // var $this = $(this);
    var key = $tab.attr('data-tab-key');
    var $share_tabs = $tab.closest('.share-tab');
    $('.tab-content', $share_tabs).hide();
    $('.tab-content[data-tab-key="' + key + '"]', $share_tabs).show();
    var share_method = $share_tabs.attr('data-share-method');
    if (share_method == 'query_param') {
      const url = new URL(location);
      url.searchParams.set("st", key);
      history.pushState({}, "", url);
    }
  }
  Drupal.behaviors.share_tabs = {

    attach: function (context, settings) {
      var hash = window.top.location.hash.substring(1);
      var $share_tabs = $('.share-tab').each(function (){
        var share_method = $(this).attr('data-share-method');
        if (share_method == 'hash') {
          $tabs = $('.tablink', this).each(function () {
            $tab = $(this);
            var key = $(this).attr('data-tab-key');
            if (hash == 'st-' + key) {
              clickShareTab($tab);
            }
          })
        }
        // else if (share_method == 'query_param')
      });
      var $share_tabs_tabs = $('.share-tab .tablink', context);
      $share_tabs_tabs.each(function() {
        
      });
      $share_tabs_tabs.click(function() {
        clickShareTab($(this));
      });
    },
  };
})(jQuery, Drupal, once);