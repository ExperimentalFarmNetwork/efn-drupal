<?php

/**
 * @file
 * Contains \Drupal\Console\Generator\ModuleGenerator.
 */

namespace Drupal\Console\Generator;

/**
 * Class ModuleGenerator
 * @package Drupal\Console\Generator
 */
class ModuleGenerator extends Generator
{
    /**
     * @param $module
     * @param $machineName
     * @param $dir
     * @param $description
     * @param $core
     * @param $package
     * @param $moduleFile
     * @param $featuresBundle
     * @param $composer
     * @param $dependencies
     * @param $test
     */
    public function generate(
        $module,
        $machineName,
        $dir,
        $description,
        $core,
        $package,
        $moduleFile,
        $featuresBundle,
        $composer,
        $dependencies,
        $test
    ) {
        $dir .= '/'.$machineName;
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" exists but is a file.',
                        realpath($dir)
                    )
                );
            }
            $files = scandir($dir);
            if ($files != array('.', '..')) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not empty.',
                        realpath($dir)
                    )
                );
            }
            if (!is_writable($dir)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unable to generate the module as the target directory "%s" is not writable.',
                        realpath($dir)
                    )
                );
            }
        }

        $parameters = array(
          'module' => $module,
          'machine_name' => $machineName,
          'type' => 'module',
          'core' => $core,
          'description' => $description,
          'package' => $package,
          'dependencies' => $dependencies,
          'test' => $test,
        );

        $this->renderFile(
            'module/info.yml.twig',
            $dir.'/'.$machineName.'.info.yml',
            $parameters
        );

        if (!empty($featuresBundle)) {
            $this->renderFile(
                'module/features.yml.twig',
                $dir.'/'.$machineName.'.features.yml',
                array(
                'bundle' => $featuresBundle,
                )
            );
        }

        if ($moduleFile) {
            $this->renderFile(
                'module/module.twig',
                $dir . '/' . $machineName . '.module',
                $parameters
            );
        }

        if ($composer) {
            $this->renderFile(
                'module/composer.json.twig',
                $dir.'/'.'composer.json',
                $parameters
            );
        }

        if ($test) {
            $this->renderFile(
                'module/src/Tests/load-test.php.twig',
                $dir . '/src/Tests/' . 'LoadTest.php',
                $parameters
            );
        }
    }
}
