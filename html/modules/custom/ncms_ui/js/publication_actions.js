(function (Drupal, once) {
  const publicationActionSelector = '[data-ncms-publication-action]';

  /**
   * Sets the disabled state of all publication actions for a form.
   *
   * @param {HTMLFormElement} form
   *   The form containing the publication actions.
   * @param {boolean} disabled
   *   Whether the actions should be disabled.
   */
  const setPublicationActionsDisabled = (form, disabled) => {
    form.ownerDocument
      .querySelectorAll(publicationActionSelector)
      .forEach((action) => {
        if (action.form === form && action.disabled !== disabled) {
          action.disabled = disabled;
        }
      });
  };

  /**
   * Extends an action's Ajax lifecycle to lock its sibling actions.
   *
   * @param {HTMLInputElement} action
   *   The publication action.
   *
   * @return {boolean}
   *   Whether the Ajax instance was available.
   */
  const registerPublicationAction = (action) => {
    const ajax = Drupal.ajax.instances.find(
      (instance) => instance && instance.element === action,
    );
    const form = action.form || action.closest('form');
    if (!ajax || !form) {
      return false;
    }
    if (ajax.options.ncmsPublicationActionsRegistered) {
      return true;
    }

    const originalBeforeSend = ajax.options.beforeSend;
    ajax.options.beforeSend = function (...args) {
      const result = originalBeforeSend.apply(this, args);
      if (result !== false) {
        setPublicationActionsDisabled(form, true);
      }
      return result;
    };

    const originalComplete = ajax.options.complete;
    ajax.options.complete = function (...args) {
      try {
        return originalComplete.apply(this, args);
      } finally {
        setPublicationActionsDisabled(form, false);
      }
    };

    ajax.options.ncmsPublicationActionsRegistered = true;
    return true;
  };

  Drupal.behaviors.ncmsPublicationActions = {
    attach(context) {
      once(
        'ncms-publication-action',
        publicationActionSelector,
        context,
      ).forEach((action) => {
        if (!registerPublicationAction(action)) {
          // Ajax behaviors can be attached later in the same behavior pass.
          window.setTimeout(() => registerPublicationAction(action));
        }
      });
    },
  };
})(Drupal, once);
