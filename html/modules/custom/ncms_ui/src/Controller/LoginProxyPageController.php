<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implementation of the LoginProxyPageController class.
 */
class LoginProxyPageController extends ControllerBase {

  /**
   * Build the title of the proxy page.
   */
  public function title() {
    return $this->config('system.site')->get('slogan') ?? '';
  }

  /**
   * Build the content of the proxy page.
   *
   * What we need is a message and a link to the login.
   */
  public function page() {

    // If the user is already logged-in, redirect to the front page.
    if ($this->currentUser()->isAuthenticated()) {
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    $message = [
      '#markup' => $this->t('This site is backend-only and you need to login in order to use it.'),
    ];

    $route_name = 'user.login';
    $options = [
      'attributes' => [
        'class' => [
          'login-link',
          'cd-button',
        ],
      ],
    ];

    $destination = $this->getRedirectDestination()->get();
    if ($destination) {
      $options['query'] = [
        'destination' => '/' . ltrim($destination, '/'),
      ];
    }

    $login_link = Link::createFromRoute($this->t('Login via Humanitarian ID'), $route_name, [], $options)->toRenderable();

    return [
      '#type' => 'container',
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'content' => $message,
      ],
      'link' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'content' => $login_link,
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];
  }

}
