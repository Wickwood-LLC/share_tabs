(function ($, Drupal, once) {
  /**
   * Initialize Social Media links
   * @param {*} context
   */
  function initSocialLinks(context) {
    $('.social-media-sharing a.share', context).each(function () {
      $this = $(this);
      if (!$this.is('[data-href]')) { // Make sure data-href is not already set.
        var href = $this.attr('href');
        $this.attr('data-href', href);
        updateSocialLink($this);
      }
    });
  }
  /**
   * Update a social media link to recalculate its href.
   * @param {*} $elem
   */
  function updateSocialLink($elem) {
    if ($elem.is('[data-href]')) {  // Make sure data-href value exist as that is required for this update action.
      var data_href = $elem.attr('data-href');
      $elem.attr('href', data_href.split("[current-page:url-dynamic]").join(encodeURIComponent(location.href)));
    }
  }
  Drupal.behaviors.twa_solcial_media_links = {
    attach: function (context, settings) {
      initSocialLinks(context);
      $(".share-tabs .tablink", context).on("shareTabClicked", function (event, data) {
        $('.social-media-sharing a.share', context).each(function () {
          updateSocialLink($(this));
        });
      });
    }
  };
})(jQuery, Drupal, once);
  