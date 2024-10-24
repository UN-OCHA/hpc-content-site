(function ($, Drupal) {
  'use strict';

  // Attach behaviors.
  Drupal.behaviors.subArticle = {
    attach: (context, settings) => {
      once('collapsible-sub-article', '.paragraph--type--sub-article[data-article-collapsible="true"] article', context).forEach((subArticle) => {
        // Find collapsible paragraphs.
        let collapsed = false;
        $(subArticle).find('.gho-sub-article__content > .paragraph').each(function (i, paragraph) {
          if ($(paragraph).hasClass('gho-top-figures') && !collapsed) {
            return;
          }
          $(paragraph).addClass('collapsible');
          $(paragraph).addClass(collapsed ? 'initially-hidden' : 'fade-out');
          collapsed = true;
        });

        if (collapsed) {
          // Add collapsible control.
          let $collapseControlOuter = $('<div>').addClass('collapsible-control--outer');
          let $collapseControl = $('<div>').addClass('collapsible-control').addClass('content-width');
          let $expandButton = $('<a />').text(Drupal.t('Read more'))
          .attr('href', '#')
          .addClass('expand-collapsible')
          .addClass('cd-button');
          $expandButton.click(function (e) {
            e.preventDefault();
            $expandButton.addClass('hidden');
            $collapsButton.removeClass('hidden');
            $(subArticle).find('.gho-sub-article__content > .paragraph.collapsible').addClass('expanded');
          });

          let $collapsButton = $('<a />').text(Drupal.t('Collapse content'))
          .attr('href', '#')
          .addClass('collaps-collapsible')
          .addClass('cd-button')
          .addClass('hidden');
          $collapsButton.click(function (e) {
            e.preventDefault();
            $expandButton.removeClass('hidden');
            $collapsButton.addClass('hidden');
            let $scroolTarget = $(subArticle).find('.gho-sub-article__content > .paragraph:nth-child(2)');
            $scroolTarget.get(0).scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => {
              $(subArticle).find('.gho-sub-article__content > .paragraph.collapsible').removeClass('expanded');
            }, 500);

          });

          $collapseControl.append($expandButton);
          $collapseControl.append($collapsButton);

          let articleUrl = $(subArticle).parents('.paragraph--type--sub-article').attr('data-article-link');
          if (articleUrl) {
            let $readmoreButton = $('<a />')
            .text(Drupal.t('Go to article page'))
            .attr('href', articleUrl)
            .addClass('read-more')
            .addClass('cd-button');
            $collapseControl.append($readmoreButton);
          }

          $collapseControlOuter.append($collapseControl);
          $(subArticle).append($collapseControlOuter);
          $(subArticle).addClass('collapsible');
        }
      });
    }
  }
})(jQuery, Drupal);
