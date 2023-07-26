(function ($, Drupal, drupalSettings) {

  Drupal.NcmsNodePreview = {
    originalTitle: null,
    updater: null,
  };

  Drupal.NcmsNodePreview.resize = function ($iframe) {
    var iframe = $iframe.get(0);
    if (!iframe || !iframe.contentWindow) {
      // Stop updating size if the iframe disappears.
      clearInterval(Drupal.NcmsNodePreview.updater);
      Drupal.NcmsNodePreview.updater = null;
      return;
    }
    iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + "px";

    // Periodically auto-resize iframe as contents may change height.
    if (Drupal.NcmsNodePreview.updater === null) {
      Drupal.NcmsNodePreview.updater = setInterval(function () {
        Drupal.NcmsNodePreview.resize($iframe);
      }, 1000);
    }
  }

  /**
   * Attaches the behavior.
   */
  Drupal.behaviors.NcmsNodePreview = {
    attach: function (context, settings) {
      let $iframe = $('iframe#node-preview', context);
      if ($iframe.length > 0) {
        // Auto-resize iframe to account for the content height.
        setTimeout(function () {
          Drupal.NcmsNodePreview.resize($iframe);
        }, 250);

        // Set the page title, this might also be used to create file names
        // when printing to PDF using default browser features.
        Drupal.NcmsNodePreview.originalTitle = document.title;
        document.title = $iframe.data('page-title');

        // And make sure that the original title is set again when the preview
        // modal closes.
        $(window).on('dialog:afterclose', (e) => {
          document.title = Drupal.NcmsNodePreview.originalTitle;
        });
      };


    }
  }
})(jQuery, Drupal, drupalSettings);

// Create a scroll function that can be called from the preview iframe.
function ncmsPreviewScrollToPoint(top) {
  jQuery('#drupal-modal').animate({ scrollTop: top }, 'fast')
}
