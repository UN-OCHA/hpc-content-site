(function ($, Drupal, drupalSettings) {

  /**
   * Attaches the behavior.
   *
   * We use this to simulate a scroll inside the iframe
   */
  Drupal.behaviors.NcmsNodeStandalone = {
    attach: function (context, settings) {
      $('a.gho-footnote-backlink').on('click', function(e) {
        if (self !== top) {
          e.preventDefault();
          let jump_mark = $(this).attr('href');
          window.parent.ncmsPreviewScrollToPoint($(jump_mark).offset().top);
        }
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
