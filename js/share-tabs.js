(function ($, Drupal, once) {
  function clickShareTab($tab) {
    var name = $tab.attr('data-tab-name');
    var $share_tabs = $tab.closest('.share-tabs');
    $('.tablink', $share_tabs).removeClass('active-tab');
    $tab.addClass('active-tab');
    $('.tab-content', $share_tabs).hide();
    var $tab_content = $('.tab-content[data-tab-name="' + name + '"]', $share_tabs);
    $tab_content.show();
    var share_method = $share_tabs.attr('data-share-method');
    var query_param_name = $share_tabs.attr('data-query-param-name');
    if (share_method == 'query_param') {
      const url = new URL(location);
      url.searchParams.set(query_param_name, name);
      history.pushState({}, "", url);
    }
    var ajax_load = $tab_content.attr('data-ajax');
    var ajax_loaded = $tab_content.attr('data-ajax-loaded');
    if (ajax_load == '1' && ajax_loaded == '0') {
      $tab_content.attr('aria-busy', 'true');
      $tab_content.html('<div class="ajax-progress ajax-progress-throbber">Loading ... <div class="throbber">&nbsp;</div></div>');
      Drupal.ajax({
        url: Drupal.url('ajax/share-tabs/tab-content'),
        type: 'POST',
        submit: {
          entity: JSON.parse($tab_content.attr('data-ajax-params')),
          tab_content_id: $tab_content.attr('id'),
        }
      })
      .execute();
    }
  }
  Drupal.behaviors.share_tabs = {

    attach: function (context, settings) {
      var hash = window.top.location.hash.substring(1);
      var $share_tabs = $('.share-tabs').each(function (){
        var share_method = $(this).attr('data-share-method');
        if (share_method == 'hash') {
          $tabs = $('.tablink', this).each(function () {
            $tab = $(this);
            var name = $(this).attr('data-tab-name');
            if (hash == 'st-' + name) {
              clickShareTab($tab);
            }
          })
        }
      });
      var $share_tabs_tabs = $('.share-tabs .tablink', context);
      $share_tabs_tabs.each(function() {
        
      });
      $share_tabs_tabs.click(function() {
        clickShareTab($(this));
      });
    },
  };
})(jQuery, Drupal, once);