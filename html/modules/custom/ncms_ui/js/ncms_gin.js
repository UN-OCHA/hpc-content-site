(function ($, Drupal, drupalSettings) {

  /**
   * Attaches the behavior.
   *
   * We use this to "correct" the back to site link.
   */
  Drupal.behaviors.NcmsGin = {
    attach: function (context, settings) {
      if (typeof settings.ncms_gin == 'undefined' || typeof settings.ncms_gin.redirect_url == 'undefined') {
        return;
      }
      redirect_url = settings.ncms_gin.redirect_url;
      $breadcrumb_link = $('.gin-breadcrumb__list li:first-child a');
      if (!$breadcrumb_link) {
        return;
      }
      $breadcrumb_link.attr('href', redirect_url);
      if (typeof settings.ncms_gin.redirect_label != 'undefined') {
        $breadcrumb_link.html(settings.ncms_gin.redirect_label);
      }
    }
  }
})(jQuery, Drupal, drupalSettings);
