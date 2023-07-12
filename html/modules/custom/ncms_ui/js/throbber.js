(function ($, Drupal, drupalSettings) {

  // Create an observer instance
  var observer = new MutationObserver(function(mutations) {
    // Traverse every mutation
    mutations.forEach(function(mutation) {
      for (var i = 0; i < mutation.addedNodes.length; i++) {
        // Test if the link element has been added to mutation.target
        if ($(mutation.addedNodes[i]).hasClass('ajax-progress--fullscreen')) {
          if (!$('.ajax-loading-overlay').length) {
            $('body').append('<div class="ajax-loading-overlay"></div>');
          }
        }
      }
      for (var i = 0; i < mutation.removedNodes.length; i++) {
        // Test if the link element has been added to mutation.target
        if ($(mutation.removedNodes[i]).hasClass('ajax-progress--fullscreen')) {
          if ($('.ajax-loading-overlay').length) {
            $('body > div.ajax-loading-overlay').remove();
          }
        }
      }
    });
  });

  // Configure the observer:
  var config = {
    attributes: true,
    childList: true,
    characterData: true,
    subtree: false
  };

  /**
   * Attaches the behavior.
   */
  Drupal.behaviors.NcmsThrobber = {
    attach: function (context, settings) {
      // var body = ;
      var $body = $(once('ajax-throbber', 'body'));
      if ($body.length === 0) {
        return;
      }
      observer.observe(document.querySelector('body'), config);
    }
  }
})(jQuery, Drupal, drupalSettings);
