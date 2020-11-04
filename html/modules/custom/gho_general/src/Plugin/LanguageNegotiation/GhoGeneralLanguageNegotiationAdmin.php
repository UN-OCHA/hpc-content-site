<?php

namespace Drupal\gho_general\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\user\Plugin\LanguageNegotiation\LanguageNegotiationUserAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

/**
 * Use site's default language for administration pages.
 *
 * @LanguageNegotiation(
 *   id = Drupal\gho_general\Plugin\LanguageNegotiation\GhoGeneralLanguageNegotiationAdmin::METHOD_ID,
 *   types = {Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE},
 *   weight = -10,
 *   name = @Translation("Default site language"),
 *   description = @Translation("Use site's default language for administration pages.")
 * )
 */
class GhoGeneralLanguageNegotiationAdmin extends LanguageNegotiationUserAdmin {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'gho-general-language-admin';

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageNegotiationUserAdmin instance.
   *
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The admin context.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $router
   *   The router.
   * @param \Drupal\Core\PathProcessor\PathProcessorManager $path_processor_manager
   *   The path processor manager.
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $stacked_route_match
   *   The stacked route match.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(AdminContext $admin_context, UrlMatcherInterface $router, PathProcessorManager $path_processor_manager, StackedRouteMatchInterface $stacked_route_match, LanguageManagerInterface $language_manager) {
    $this->adminContext = $admin_context;
    $this->router = $router;
    $this->pathProcessorManager = $path_processor_manager;
    $this->stackedRouteMatch = $stacked_route_match;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('router.admin_context'),
      $container->get('router'),
      $container->get('path_processor_manager'),
      $container->get('current_route_match'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    // Force the use of the site's default language for the admin pages.
    if ($this->isAdminPath($request)) {
      return $this->languageManager->getDefaultLanguage()->getId();
    }
    return NULL;
  }

}
