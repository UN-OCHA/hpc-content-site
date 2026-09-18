(function ($, Drupal, drupalSettings) {

  var overlayUpdateTimer = null;

  function isFullscreenProgress(element) {
    return $(element).hasClass('ajax-progress--fullscreen') || $(element).hasClass('ajax-progress-fullscreen');
  }

  function isModalOverlay(element) {
    return $(element).hasClass('ui-widget-overlay');
  }

  function hasFullscreenProgress() {
    return $('.ajax-progress--fullscreen, .ajax-progress-fullscreen').length > 0;
  }

  function hasModalOverlay() {
    return $('.ui-widget-overlay').length > 0;
  }

  function shouldShowOverlay() {
    return hasFullscreenProgress() && !hasModalOverlay();
  }

  function updateOverlay() {
    if (shouldShowOverlay()) {
      if (!$('.ajax-loading-overlay').length) {
        $('body').append('<div class="ajax-loading-overlay"></div>');
      }
    }
    else {
      $('body > div.ajax-loading-overlay').remove();
    }
  }

  function scheduleOverlayUpdate() {
    clearTimeout(overlayUpdateTimer);
    overlayUpdateTimer = setTimeout(updateOverlay, 0);
  }

  // Create an observer instance
  var observer = new MutationObserver(function(mutations) {
    // Traverse every mutation
    mutations.forEach(function(mutation) {
      for (var i = 0; i < mutation.addedNodes.length; i++) {
        // Modal dialogs already add their own overlay. Avoid stacking ours on
        // top, since two translucent layers visibly change the backdrop tone.
        if (isFullscreenProgress(mutation.addedNodes[i]) || isModalOverlay(mutation.addedNodes[i])) {
          updateOverlay();
        }
      }
      for (var i = 0; i < mutation.removedNodes.length; i++) {
        if (isFullscreenProgress(mutation.removedNodes[i]) || isModalOverlay(mutation.removedNodes[i])) {
          scheduleOverlayUpdate();
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
