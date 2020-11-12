/**
 * Observe the footnote references entering and leaving the viewport
 * and display them in a popup at the bottom of the screen when scrolling.
 */
(function () {
  'use strict';

  /**
   * Set the visibility of the footnotes in the footnote list popup.
   */
  function setFootnoteVisibility(id, visible) {
    var footnote = document.getElementById(id);
    if (footnote) {
      if (visible === true) {
        footnote.setAttribute('data-visible', '');
      }
      else {
        footnote.removeAttribute('data-visible');
      }
    }
  }

  /**
   * Set the visibility of the footnote list popup based on its content.
   */
  function updateFootnoteList(list) {
    // Skip if the no popup flag is one, meaning the reference list is
    // visible on the screen.
    if (list.hasAttribute('data-no-popup')) {
      list.removeAttribute('data-visible');
    }
    else {
      var elements = list.querySelectorAll('.gho-footnote[data-visible]');
      if (elements.length > 0) {
        list.setAttribute('data-visible', '');
      }
      else {
        list.removeAttribute('data-visible');
      }
    }
  }

  /**
   * Observe the intersection of the references with the top half of the window.
   *
   * When a reference enters the top half of the window, we makes the reference
   * list popup with the reference visible.
   */
  function observeReferences(list) {
    var intersectionObserver = new IntersectionObserver(function (entries) {
      for (var i = 0, l = entries.length; i < l; i++) {
        var entry = entries[i];
        setFootnoteVisibility(entry.target.hash.substr(1), entry.isIntersecting);
      }
      updateFootnoteList(list);
    }, {
      // The bottom margin is to avoid showing the footnotes popup over the
      // footnotes that interesected with the root. It corresponds to the
      // max-height of the list in css.
      rootMargin: '0px 0px -50% 0px',
      threshold: 0.5
    });

    var elements = document.querySelectorAll('.gho-footnote-reference a');
    for (var i = 0, l = elements.length; i < l; i++) {
      intersectionObserver.observe(elements[i]);
    }
  }

  /**
   * Obersev the interesection of the reference list.
   *
   * When the reference list appears at the bottom of the page we set a flag
   * to disable the popup behavior.
   *
   * Note: we could also call "unobserve" on the reference observer but not
   * sure if that would change much in terms of performances.
   */
  function observeList(list) {
    var intersectionObserver = new IntersectionObserver(function (entries) {
      if (entries.length > 0) {
        if (entries[0].isIntersecting) {
          list.setAttribute('data-no-popup', '');
        }
        else {
          list.removeAttribute('data-no-popup');
        }
      }
    }, {
      // This is different from the other observer to give us some margin (pun
      // non intended?) before disabling the popup behavior.
      rootMargin: '0px 0px -20% 0px'
    });
    intersectionObserver.observe(list.parentNode);
  }

  // Prevent the footnotes from being processed several times.
  var list = document.querySelector('.gho-footnote-list--accumulated .gho-footnote-list__wrapper');
  if (!list || list.hasAttribute('data-footnotes-processed')) {
    return;
  }
  list.setAttribute('data-footnotes-processed', '');


  // Observe the footnote references entering and leaving the viewport
  // and display them in a popup at the bottom of the screen.
  if ('IntersectionObserver' in window) {
    observeReferences(list);
    observeList(list);
  }

})();
