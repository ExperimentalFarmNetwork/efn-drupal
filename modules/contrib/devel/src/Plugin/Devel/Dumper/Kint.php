<?php

namespace Drupal\devel\Plugin\Devel\Dumper;

use Drupal\devel\DevelDumperBase;
use Kint\Parser\BlacklistPlugin;
use Kint\Renderer\RichRenderer;
use Psr\Container\ContainerInterface;

/**
 * Provides a Kint dumper plugin.
 *
 * @DevelDumper(
 *   id = "kint",
 *   label = @Translation("Kint"),
 *   description = @Translation("Wrapper for <a href='https://github.com/kint-php/kint'>Kint</a> debugging tool."),
 * )
 */
class Kint extends DevelDumperBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configure();
  }

  /**
   * Configures kint with more sane values.
   */
  protected function configure() {
    // Remove resource-hungry plugins.
    \Kint::$plugins = array_diff(\Kint::$plugins, [
      'Kint\\Parser\\ClassMethodsPlugin',
      'Kint\\Parser\\ClassStaticsPlugin',
      'Kint\\Parser\\IteratorPlugin',
    ]);
    \Kint::$aliases = $this->getInternalFunctions();

    RichRenderer::$folder = FALSE;
    BlacklistPlugin::$shallow_blacklist[] = ContainerInterface::class;
  }

  /**
   * {@inheritdoc}
   */
  public function export($input, $name = NULL) {
    ob_start();
    if ($name == '__ARGS__') {
      call_user_func_array(['Kint', 'dump'], $input);
      $name = NULL;
    }
    else {
      \Kint::dump($input);
    }
    $dump = ob_get_clean();

    // Kint does't allow to assign a title to the dump. Workaround to use the
    // passed in name as dump title.
    if ($name) {
      $dump = preg_replace('/\$input/', $name, $dump, 1);
    }

    return $this->setSafeMarkup($dump);
  }

  /**
   * {@inheritdoc}
   */
  public static function checkRequirements() {
    return class_exists('Kint', TRUE);
  }

}
