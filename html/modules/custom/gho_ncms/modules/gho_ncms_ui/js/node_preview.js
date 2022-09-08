(function ($, Drupal, drupalSettings) {

  Drupal.ghoNodePreview = {};
  Drupal.ghoNodePreview.updater = null;
  Drupal.ghoNodePreview.resize = function ($iframe) {
    var iframe = $iframe.get(0);
    if (!iframe || !iframe.contentWindow) {
      clearInterval(Drupal.ghoNodePreview.updater);
      return;
    }
    iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + "px";
  }

  /**
   * Attaches the behavior.
   */
  Drupal.behaviors.ghoNodePreview = {
    attach: function (context, settings) {
      let $iframe = $('iframe#node-preview');
      if ($iframe.length > 0) {
        // Periodically auto-resize iframe as contents may change height.
        Drupal.ghoNodePreview.updater = setInterval(function () {
          Drupal.ghoNodePreview.resize($iframe);
        }, 1000);
      };
    }
  }
})(jQuery, Drupal, drupalSettings);
