<?php

/**
 * @file
 * Contains \Drupal\Tests\ng_lightbox\Unit\NgLightboxTest
 */

namespace Drupal\Tests\ng_lightbox\Unit;

use Drupal\ng_lightbox\NgLightbox;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\ng_lightbox\NgLightbox
 * @group ng_lightbox
 */
class NgLightboxTest extends UnitTestCase {

  /**
   * Test with an External URL.
   */
  public function testExternalUrl() {
    $lightbox = $this->getLightbox();
    $this->assertEquals(FALSE, $lightbox->isNgLightboxEnabledPath($this->getUrlMock(TRUE)->reveal()));
  }

  /**
   * Test the admin_skip_path settings.
   */
  public function testAdminSkipPaths() {
    // Admin skip paths enabled and admin route.
    $lightbox = $this->getLightbox();
    $this->assertEquals(FALSE, $lightbox->isNgLightboxEnabledPath($this->getUrlMock()->reveal()));
  }

  /**
   * Test with an empty path.
   */
  public function testEmptyPath() {
    $lightbox = $this->getLightbox(FALSE);
    $url = $this->getUrlMock();
    $url->toString()->willReturn('');
    $this->assertEquals(FALSE, $lightbox->isNgLightboxEnabledPath($url->reveal()));
  }

  /**
   * Helper to create Url mocks.
   *
   * @param bool|FALSE $is_external
   *   TRUE if this URL is external otherwise FALSE.
   *
   * @return \Prophecy\Prophecy\ObjectProphecy
   *   The url prophecy for testing.
   */
  protected function getUrlMock($is_external = FALSE) {
    $url = $this->prophesize('Drupal\Core\Url');
    $url->isExternal()->willReturn($is_external);
    return $url;
  }

  /**
   * Get the lightbox service setup for testing.
   *
   * @return \Drupal\ng_lightbox\NgLightbox
   *   The lightbox service.
   */
  protected function getLightbox($skip_admin_paths = TRUE, $is_admin_route = TRUE) {

    $path_matcher = $this->prophesize('Drupal\Core\Path\PathMatcherInterface');
    $alias_manager = $this->prophesize('Drupal\Core\Path\AliasManagerInterface');
    $config_factory = $this->prophesize('Drupal\Core\Config\ConfigFactoryInterface');
    $config = $this->prophesize('Drupal\Core\Config\ImmutableConfig');
    $config->get(Argument::exact('skip_admin_paths'))->willReturn($skip_admin_paths);
    $config_factory->get(Argument::exact('ng_lightbox.settings'))->willReturn($config);
    $admin_context = $this->prophesize('Drupal\Core\Routing\AdminContext');
    $admin_context->isAdminRoute()->willReturn($is_admin_route);

    $lightbox = new NgLightbox($path_matcher->reveal(), $alias_manager->reveal(), $config_factory->reveal(), $admin_context->reveal());

    return $lightbox;
  }

}
