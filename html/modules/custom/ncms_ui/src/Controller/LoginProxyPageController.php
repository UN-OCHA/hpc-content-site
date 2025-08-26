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
      [
        '#markup' => $this->t('Please log in to access the @site_name.', [
          '@site_name' => $this->config('system.site')->get('name'),
        ]),
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('If you have a UN agency account you can log in directly, but still also need to be an authorized user of the Content Module to get access. Contact <a href="mailto:ocha-hpc@un.org">ocha-hpc@un.org</a> to request this.'),
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('If you do not have a UN agency account, then you will first need your email address to be added to an approved user list. Contact <a href="mailto:ocha-hpc@un.org">ocha-hpc@un.org</a> to request this. Once added, you can use the ‘UN agency’ link above with that email address to log in.'),
      ],
    ];

    $route_name = 'ocha_entraid.login';
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

    $login_link = Link::createFromRoute($this->t('Continue with your UN Agency email'), $route_name, [], $options)->toRenderable();

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
