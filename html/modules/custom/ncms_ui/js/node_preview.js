(function ($, Drupal, drupalSettings) {

  Drupal.NcmsNodePreview = {};
  Drupal.NcmsNodePreview.updater = null;
  Drupal.NcmsNodePreview.resize = function ($iframe) {
    var iframe = $iframe.get(0);
    if (!iframe || !iframe.contentWindow) {
      clearInterval(Drupal.NcmsNodePreview.updater);
      return;
    }
    iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + "px";
  }

  /**
   * Attaches the behavior.
   */
  Drupal.behaviors.NcmsNodePreview = {
    attach: function (context, settings) {
      let $iframe = $('iframe#node-preview');
      if ($iframe.length > 0) {
        // Periodically auto-resize iframe as contents may change height.
        Drupal.NcmsNodePreview.updater = setInterval(function () {
          Drupal.NcmsNodePreview.resize($iframe);
        }, 1000);
      };
    }
  }
})(jQuery, Drupal, drupalSettings);
