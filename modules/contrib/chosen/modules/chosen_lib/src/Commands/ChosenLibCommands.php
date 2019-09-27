<?php

namespace Drupal\chosen_lib\Commands;

use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LogLevel;

/**
 * The Chosen plugin URI.
 */
define('CHOSEN_DOWNLOAD_URI', 'https://github.com/harvesthq/chosen/releases/download/v1.8.7/chosen_v1.8.7.zip');

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ChosenLibCommands extends DrushCommands {

  /**
   * Download and install the Chosen plugin.
   *
   * @param $path
   *   Optional. A path where to install the Chosen plugin. If omitted Drush will use the default location.
   *
   * @command chosen:plugin
   * @aliases chosenplugin,chosen-plugin
   * @throws \Exception
   */
  public function plugin($path = '') {
    if (empty($path)) {
      $path = 'libraries';
    }

    // Create the path if it does not exist.
    if (!is_dir($path)) {
      drush_op('mkdir', $path);
      $this->drush_log(dt('Directory @path was created', ['@path' => $path]), 'notice');
    }

    // Set the directory to the download location.
    $olddir = getcwd();
    chdir($path);

    // Download the zip archive.
    if ($filepath = $this->drush_download_file(CHOSEN_DOWNLOAD_URI)) {
      $filename = basename($filepath);
      $dirname = basename($filepath, '.zip');

      // Remove any existing Chosen plugin directory.
      if (is_dir($dirname) || is_dir('chosen')) {
        drush_delete_dir($dirname, TRUE);
        drush_delete_dir('chosen', TRUE);
        $this->drush_log(dt('A existing Chosen plugin was deleted from @path', ['@path' => $path]), 'notice');
      }

      // Decompress the zip archive.
      $this->drush_tarball_extract($filename, $dirname);

      // Change the directory name to "chosen" if needed.
      if ($dirname != 'chosen') {
        $this->drush_move_dir($dirname, 'chosen');
        $dirname = 'chosen';
      }

      unlink($filename);
    }

    if (is_dir($dirname)) {
      $this->drush_log(dt('Chosen plugin has been installed in @path', ['@path' => $path]), 'success');
    }
    else {
      $this->drush_log(dt('Drush was unable to install the Chosen plugin to @path', ['@path' => $path]), 'error');
    }

    // Set working directory back to the previous working directory.
    chdir($olddir);
  }

  /**
   * @param $message
   * @param $type
   */
  public function drush_log($message, $type = LogLevel::INFO) {
    $this->logger()->log($type, $message);
  }

  /**
   * @param $url
   * @param bool $destination
   * @return bool|string
   * @throws \Exception
   */
  public function drush_download_file($url, $destination = FALSE) {
    // Generate destination if omitted.
    if (!$destination) {
      $file = basename(current(explode('?', $url, 2)));
      $destination = getcwd() . '/' . basename($file);
    }

    // Copied from: \Drush\Commands\SyncViaHttpCommands::downloadFile
    static $use_wget;
    if ($use_wget === NULL) {
      $use_wget = drush_shell_exec('which wget');
    }

    $destination_tmp = drush_tempnam('download_file');
    if ($use_wget) {
      drush_shell_exec("wget -q --timeout=30 -O %s %s", $destination_tmp, $url);
    }
    else {
      drush_shell_exec("curl -s -L --connect-timeout 30 -o %s %s", $destination_tmp, $url);
    }
    if (!drush_file_not_empty($destination_tmp) && $file = @file_get_contents($url)) {
      @file_put_contents($destination_tmp, $file);
    }
    if (!drush_file_not_empty($destination_tmp)) {
      // Download failed.
      throw new \Exception(dt("The URL !url could not be downloaded.", ['!url' => $url]));
    }
    if ($destination) {
      $fs = new Filesystem();
      $fs->rename($destination_tmp, $destination, TRUE);
      return $destination;
    }
    return $destination_tmp;
  }

  /**
   * @param $src
   * @param $dest
   * @return bool
   */
  public function drush_move_dir($src, $dest) {
    $fs = new Filesystem();
    $fs->rename($src, $dest, TRUE);
    return TRUE;
  }

  /**
   * @param $path
   * @return bool
   */
  public function drush_mkdir($path) {
    $fs = new Filesystem();
    $fs->mkdir($path);
    return TRUE;
  }

  /**
   * @param $path
   * @param bool $destination
   * @return mixed
   * @throws \Exception
   */
  public function drush_tarball_extract($path, $destination = FALSE) {
    $this->drush_mkdir($destination);
    if (preg_match('/\.tgz$/', $path)) {
      $return = drush_shell_cd_and_exec(dirname($path), "tar -xvzf %s -C %s", $path, $destination);
      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, drush_shell_exec_output()), ['!filename' => $path]));
      }
    }
    else {
      $return = drush_shell_cd_and_exec(dirname($path), "unzip %s -d %s", $path, $destination);
      if (!$return) {
        throw new \Exception(dt('Unable to extract !filename.' . PHP_EOL . implode(PHP_EOL, drush_shell_exec_output()), ['!filename' => $path]));
      }
    }
    return $return;
  }

}
