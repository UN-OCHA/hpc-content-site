<?php

namespace Drupal\ncms_ui\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implementation of the LoginProxyPageController class.
 */
class LoginProxyPageController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    /** @var self $instance */
    $instance = new static();
    $instance->blockManager = $container->get('plugin.manager.block');
    return $instance;
  }

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
        '#value' => $this->t('If you do not have a UN agency account, then you will first need your email address to be added to an approved user list. Contact <a href="mailto:ocha-hpc@un.org">ocha-hpc@un.org</a> to request this. Once added, you can use the ‘UN agency’ link below with that email address to log in.'),
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

    $login_attributes = [
      'attributes' => [
        'class' => [Html::getClass('login-link--un')],
      ],
    ];
    $login_link = Link::createFromRoute($this->t('Continue with your UN Agency email'), $route_name, [], NestedArray::mergeDeep($options, $login_attributes));

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['site-login'],
      ],
      'message' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'content' => $message,
      ],
      'link' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'content' => $login_link->toRenderable(),
      ],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];

    if ($this->moduleHandler()->moduleExists('social_auth')) {
      /** @var \Drupal\social_auth\Plugin\Block\SocialAuthLoginBlock $social_auth_login_block */
      $social_auth_login_block = $this->blockManager->createInstance('social_auth_login');
      /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface[] $login_networks */
      $login_networks = $social_auth_login_block->build()['#networks'] ?? [];
      $build['social_auth'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['site-login-other'],
        ],
        '#access' => !empty($login_networks),
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('You can also login using one of the following options:'),
        ],
        'links' => [
          '#type' => 'html_tag',
          '#tag' => 'div',
        ],
      ];

      foreach ($login_networks as $login_network) {
        $login_attributes = [
          'class' => [Html::getClass('login-link--' . $login_network->getShortName())],
        ];
        $login_url = $login_network->getRedirectUrl();
        $login_url->setOption('attributes', NestedArray::mergeDeep($options['attributes'], $login_attributes));
        $link = Link::fromTextAndUrl($login_network->getSocialNetwork(), $login_url);
        $build['social_auth']['links'][] = $link->toRenderable();
      }
    }

    return $build;
  }

}
