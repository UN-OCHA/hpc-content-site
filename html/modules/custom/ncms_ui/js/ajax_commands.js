(function (Drupal) {
  Drupal.AjaxCommands.prototype.reloadPage = function (ajax, response) {
    window.location.reload();
  };

})(Drupal);
