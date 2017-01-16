<?php

namespace Drupal\ng_lightbox;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

class NgLightbox {

  /**
   * The default modal when none is selected.
   */
  const DEFAULT_MODAL = 'drupal_modal';

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * An array of paths that were already checked and their match status.
   *
   * @var array
   */
  protected $matches = [];

  /**
   * Constructs a new NgLightbox service.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   Patch matcher services for comparing the lightbox patterns.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   Alias manager so we can also test path aliases.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory so we can get the lightbox settings.
   */
  public function __construct(PathMatcherInterface $path_matcher, AliasManagerInterface $alias_manager, ConfigFactoryInterface $config_factory, AdminContext $admin_context) {
    $this->pathMatcher = $path_matcher;
    $this->aliasManager = $alias_manager;
    $this->config = $config_factory->get('ng_lightbox.settings');
    $this->adminContext = $admin_context;
  }

  /**
   * Checks whether a give path matches the ng-lightbox path rules.
   * This function checks both internal paths and aliased paths.
   *
   * @param \Drupal\Core\Url $url
   *   The Url object.
   *
   * @return bool
   *   TRUE if it matches the given rules.
   */
  public function isNgLightboxEnabledPath(Url $url) {

    // No lightbox on external Urls.
    if ($url->isExternal()) {
      return FALSE;
    }

    // If we don't want to enable the Lightbox on admin pages.
    if ($this->config->get('skip_admin_paths') && $this->adminContext->isAdminRoute()) {
      return FALSE;
    }

    // @TODO, decide whether we want to try and support paths or to adopt routes
    // like core is trying to force us into.
    $path = strtolower($url->toString());

    // We filter out empty paths because some modules (such as Media) use
    // theme_link() to generate links with empty paths.
    if (empty($path)) {
      return FALSE;
    }

    // Remove the base path.
    if ($base_path = \Drupal::request()->getBasePath()) {
      $path = substr($path, strlen($base_path));
    }

    // Check the cache, see if we've handled this before.
    if (isset($this->matches[$path])) {
      return $this->matches[$path];
    }

    // Normalise the patterns as well so they match the normalised paths.
    $patterns = strtolower($this->config->get('patterns'));

    // Check for internal paths first which is much quicker than the alias lookup.
    if ($this->pathMatcher->matchPath($path, $patterns)) {
      $this->matches[$path] = TRUE;
    }
    else {
      // Now check for aliases paths.
      $aliased_path = strtolower($this->aliasManager->getAliasByPath($path));
      if ($path != $aliased_path && $this->pathMatcher->matchPath($aliased_path, $patterns)) {
        $this->matches[$path] = TRUE;
      }
      else {
        // No match.
        $this->matches[$path] = FALSE;
      }
    }

    return $this->matches[$path];
  }

  /**
   * Adds a lightbox to a link.
   *
   * @param array $link
   *   The link we want to add the lightbox to.
   */
  public function addLightbox(array &$link) {
    // Safety check if class isn't an array.
    if (!isset($link['options']['attributes']['class'])) {
      $link['options']['attributes']['class'] = array();
    }

    // Add our lightbox class.
    $link['options']['attributes']['class'][] = 'use-ajax';
    $link['options']['attributes']['data-dialog-type'] = str_replace('drupal_', '', $this->config->get('renderer') ?: static::DEFAULT_MODAL);
    $data = [
      'width' => $this->config->get('default_width'),
    ];

    $link['options']['attributes']['data-dialog-options'] = json_encode($data);
  }

}
