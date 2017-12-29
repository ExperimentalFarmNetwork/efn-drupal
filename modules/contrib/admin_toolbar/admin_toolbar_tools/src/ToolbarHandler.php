<?php

namespace Drupal\admin_toolbar_tools;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Toolbar integration handler.
 */
class ToolbarHandler implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The module service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * ToolbarHandler constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module service.
   */
  public function __construct(ModuleHandler $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Lazy builder callback for the admin_toolbar_tool items.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  public function lazyBuilder() {
    // Render the pre_render callback we disabled earlier.
    $build = admin_toolbar_prerender_toolbar_administration_tray([]);

    // Add links that are uncacheable.
    // Core toolbar module calculates cachability in advance so we have to build
    // a fake menu tree here, including access checks.
    $tools_menu = &$build['administration_menu']['#items']['admin_toolbar_tools.help']['below'];

    // Adding the 'Flush all caches' menu in the correct place.
    $menu_render_array = $this->createMenuRenderArray('admin_toolbar_tools.flush', $this->t('Flush all caches'), TRUE);
    $this->arrayInsert($tools_menu, 1, $menu_render_array);

    // Adding the submenus to 'Flush all caches' menu.
    if (!empty($tools_menu['admin_toolbar_tools.flush'])) {
      $tools_sub_menu = &$tools_menu['admin_toolbar_tools.flush']['below'];
      $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.cssjs', $this->t('Flush CSS and Javascript'));
      $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.plugin', $this->t('Flush plugins cache'));
      $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.flush_static', $this->t('Flush static cache'));
      $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.flush_menu', $this->t('Flush routing and links  cache'));
      $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.flush_rendercache', $this->t('Flush render cache'));

      // Adding a menu link to clean the Views cache.
      if ($this->moduleHandler->moduleExists('views')) {
        $tools_sub_menu += $this->createMenuRenderArray('admin_toolbar_tools.flush_views', $this->t('Flush views cache'));
      }
    }

    // Adding the 'Run Cron' menu in the correct place.
    $menu_render_array = $this->createMenuRenderArray('system.run_cron', $this->t('Run cron'));
    $this->arrayInsert($tools_menu, 3, $menu_render_array);

    return $build;
  }

  /**
   * Create the menu render array.
   *
   * @param string $route
   *    The route.
   * @param string $title
   *    The menu title.
   * @param bool $submenu
   *    Specify if the current menu element have a submenu.
   *
   * @return array
   *   A renderable array as expected by the renderer service.
   */
  private function createMenuRenderArray($route, $title, $submenu = FALSE) {
    $data = [];
    $url = Url::fromRoute($route);
    if ($url->access()) {
      $data[$route] = [
        'title' => $title,
        'url' => $url,
        'attributes' => new Attribute(['class' => ['menu-item'] + ($submenu ? ['menu-item--expanded'] : [])]),
      ];
      if ($submenu) {
        $data[$route]['below'] = [];
        $data[$route]['is_expanded'] = TRUE;
      }
    }
    return $data;
  }

  /**
   * Insert an array in a given position of another array.
   *
   * @param array $array
   *    The array where we need to insert new elements.
   * @param int $position
   *    The position where we will add the new array.
   * @param array $insert_array
   *    The array that will be inserted.
   *
   * @see http://php.net/manual/en/function.array-splice.php#56794
   */
  private function arrayInsert(array &$array, $position, array $insert_array) {
    // Getting the first part of the array.
    $first_array = array_splice($array, 0, $position);
    // Inserting the new part in the desired position.
    $array = array_merge($first_array, $insert_array, $array);
  }

}
