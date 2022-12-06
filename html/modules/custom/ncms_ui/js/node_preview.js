(function ($, Drupal, drupalSettings) {

  Drupal.NcmsNodePreview = {
    originalTitle: null
  };
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
      console.log('attach');
      let $iframe = $('iframe#node-preview');
      if ($iframe.length > 0) {
        // Periodically auto-resize iframe as contents may change height.
        Drupal.NcmsNodePreview.updater = setInterval(function () {
          Drupal.NcmsNodePreview.resize($iframe);
        }, 1000);

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
  jQuery('#drupal-modal').animate({ scrollTop: top }, "slow")
}
