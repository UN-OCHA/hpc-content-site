(function (Drupal, $, FloatingUIDOM) {

  Drupal.ncmsTooltip = function (trigger, tooltip) {
    this.trigger = trigger;
    this.tooltip = tooltip;
  }

  Drupal.ncmsTooltip.prototype.setUpTooltip = function () {

    // When the floating element is open on the screen
    const cleanup = FloatingUIDOM.autoUpdate(this.trigger, this.tooltip, () => {
      let config = {
        placement: 'top',
      }
      FloatingUIDOM.computePosition(this.trigger, this.tooltip, config).then(({x, y}) => {
        Object.assign(this.tooltip.style, {
          left: `${x}px`,
          top: `${y}px`,
        });
      });
    });
    $(this).on('mouseout', function () {
      cleanup();
    });
  };

  Drupal.ncmsTooltip.prototype.showTooltip = function () {
    $(this.tooltip).removeClass('visually-hidden');
    this.tooltip.style.display = "block";
    this.setUpTooltip();
  };

  Drupal.ncmsTooltip.prototype.hideTooltip = function () {
    $(this.tooltip).addClass('visually-hidden');
    this.tooltip.style.display = "none";
  }

  /**
   * Enable tooltip elements.
   */
  Drupal.behaviors.tooltips = {
    attach: function (context, settings) {

      $('.tooltip-wrapper', context).each(function () {
        let $trigger = $('[data-tooltip]', this);
        let $tooltip = $('.tooltip', this);

        let tooltip = new Drupal.ncmsTooltip($trigger[0], $tooltip[0]);

        $trigger.on('mouseenter', function () {
          tooltip.showTooltip()
        });

        $trigger.on('mouseout', function () {
          tooltip.hideTooltip()
        });
      });

    },
  }

})(Drupal, jQuery, FloatingUIDOM);
