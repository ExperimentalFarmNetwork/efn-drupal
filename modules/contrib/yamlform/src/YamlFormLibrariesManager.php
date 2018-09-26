<?php

namespace Drupal\yamlform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Form libraries manager.
 */
class YamlFormLibrariesManager implements YamlFormLibrariesManagerInterface {

  use StringTranslationTrait;

  /**
   * Libraries that provides additional functionality to the YAML Form module.
   *
   * @var array
   */
  protected $libraries;

  /**
   * Constructs a YamlFormLibrariesManager object.
   */
  public function __construct() {
    $this->libraries = $this->initLibraries();
  }

  /**
   * {@inheritdoc}
   */
  public function requirements() {
    $cdn = \Drupal::config('yamlform.settings')->get('library.cdn', FALSE);

    $status = [];
    $libraries = $this->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $library_path = '/' . $library['destination'] . '/' . $library['directory_name'];
      $library_exists = (file_exists(DRUPAL_ROOT . $library_path)) ? TRUE : FALSE;

      $t_args = [
        '@title' => $library['title'],
        '@version' => $library['version'],
        '@path' => $library_path,
        ':download_href' => $library['download']['url'],
        ':library_href' => $library['url']->toString(),
        ':install_href' => 'http://cgit.drupalcode.org/yamlform/tree/INSTALL.md?h=8.x-1.x',
        ':external_href' => 'https://www.drupal.org/docs/8/theming-drupal-8/adding-stylesheets-css-and-javascript-js-to-a-drupal-8-theme#external',
        ':settings_href' => Url::fromRoute('yamlform.settings', [], ['fragment' => 'edit-library'])->toString(),
      ];

      if ($library_exists) {
        $value = $this->t('@version (Installed)', $t_args);
        $description = $this->t('The <a href=":library_href">@title</a> library is installed in <b>@path</b>.', $t_args);
        $severity = REQUIREMENT_OK;
      }
      elseif ($cdn) {
        $value = $this->t('@version (CDN).', $t_args);
        $description = $this->t('The <a href=":library_href">@title</a> library is <a href=":external_href">externally hosted libraries</a> and loaded via a Content Delivery Network (CDN).', $t_args);
        $severity = REQUIREMENT_OK;
      }
      else {
        $value = $this->t('@version (CDN).', $t_args);
        $description = $this->t('Please download the <a href=":library_href">@title</a> library from <a href=":download_href">:download_href</a> and copy it to <b>@path</b> or use <a href=":install_href">Drush</a> to install this library. (<a href=":settings_href">Disable CDN warning)', $t_args);
        $severity = REQUIREMENT_WARNING;
      }

      $status['yamlform_library_' . $library_name] = [
        'library' => $library ,
        'title' => $this->t('YAML Form library: @title', $t_args),
        'value' => $value,
        'description' => $description,
        'severity' => $severity,
      ];
    }

    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrary($name) {
    return $this->libraries[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($category = NULL) {
    $libraries = $this->libraries;
    if ($category) {
      foreach ($libraries as $project_name => $project) {
        if ($project['category'] != $category) {
          unset($libraries[$project_name]);
        }
      }
    }
    return $libraries;
  }

  /**
   * Initialize libraries.
   *
   * @return array
   *   An associative array containing libraries.
   */
  protected function initLibraries() {
    $libraries = [];

    $libraries['codemirror'] = [
      'title' => $this->t('Code Mirror'),
      'description' => $this->t('Code Mirror is a versatile text editor implemented in JavaScript for the browser.'),
      'notes' => $this->t('Code Mirror is used to provide a text editor for YAML, HTML, CSS, and JavaScript configuration settings and messages.'),
      'url' => Url::fromUri('http://codemirror.net/'),
      'version' => '5.21.0',
    ];
    $libraries['ckeditor'] = [
      'title' => $this->t('CKEditor'),
      'description' => $this->t('The standard version of the CKEditor.'),
      'notes' => $this->t('Allows the YAML Form module to implement a basic and simpler CKEditor.'),
      'url' => Url::fromUri('http://ckeditor.com/'),
      'version' => '4.5.11',
    ];
    $libraries['geocomplete'] = [
      'title' => $this->t('jQuery Geocoding and Places Autocomplete Plugin'),
      'description' => $this->t("Geocomple is an advanced jQuery plugin that wraps the Google Maps API's Geocoding and Places Autocomplete services."),
      'notes' => $this->t('Geocomplete is used by the location element.'),
      'url' => Url::fromUri('http://ubilabs.github.io/geocomplete/'),
      'version' => '1.7.0',
    ];
    $libraries['inputmask'] = [
      'title' => $this->t('jQuery Input Mask'),
      'description' => $this->t('Input masks ensures a predefined format is entered. This can be useful for dates, numerics, phone numbers, etc...'),
      'notes' => $this->t('Input masks are used to ensure predefined and custom formats for text fields.'),
      'url' => Url::fromUri('http://robinherbots.github.io/jquery.inputmask/'),
      'version' => '3.3.3',
    ];
    $libraries['rateit'] = [
      'title' => $this->t('RateIt'),
      'description' => $this->t("Rating plugin for jQuery. Fast, progressive enhancement, touch support, customizable (just swap out the images, or change some CSS), unobtrusive JavaScript (using HTML5 data-* attributes), RTL support. The Rating plugin supports as many stars as you'd like, and also any step size."),
      'notes' => $this->t('RateIt is used to provide a customizable rating form element.'),
      'version' => '1.1.1',
      'url' => Url::fromUri('https://github.com/gjunge/rateit.js'),
    ];
    $libraries['select2'] = [
      'title' => $this->t('Select2'),
      'description' => $this->t('Select2 gives you a customizable select box with support for searching and tagging.'),
      'notes' => $this->t('Select2 is used to improve the user experience for select menus.'),
      'version' => '4.0.3',
      'url' => Url::fromUri('https://select2.github.io/'),
    ];
    $libraries['signature_pad'] = [
      'title' => $this->t('Signature Pad'),
      'description' => $this->t("Signature Pad is a JavaScript library for drawing smooth signatures. It is HTML5 canvas based and uses variable width Bézier curve interpolation. It works in all modern desktop and mobile browsers and doesn't depend on any external libraries."),
      'notes' => $this->t('Signature Pad is used to provide a signature element.'),
      'url' => Url::fromUri('https://github.com/szimek/signature_pad'),
      'version' => '1.5.3',
    ];
    $libraries['timepicker'] = [
      'title' => $this->t('jQuery Timepicker'),
      'description' => $this->t('A lightweight, customizable javascript timepicker plugin for jQuery, inspired by Google Calendar.'),
      'notes' => $this->t('Timepicker is used to provide a polyfill for HTML 5 time elements.'),
      'version' => '1.11.8',
      'url' => Url::fromUri('https://github.com/jonthornton/jquery-timepicker'),
    ];
    $libraries['toggles'] = [
      'title' => $this->t('jQuery Toggles'),
      'description' => $this->t('Toggles is a lightweight jQuery plugin that creates easy-to-style toggle buttons.'),
      'notes' => $this->t('Toggles is used to provide a toggle element.'),
      'version' => 'v4.0.0',
      'url' => Url::fromUri('https://github.com/simontabor/jquery-toggles/'),
    ];
    $libraries['word-and-character-counter'] = [
      'title' => $this->t('jQuery Word and character counter plug-in!'),
      'description' => $this->t('The jQuery word and character counter plug-in allows you to count characters or words'),
      'notes' => $this->t('Word or character counting, with server-side validation, is available for text fields and text areas.'),
      'version' => '1.6.0',
      'url' => Url::fromUri('https://github.com/qwertypants/jQuery-Word-and-Character-Counter-Plugin'),
    ];

    // Append library info from 'yamlform.libraries.make.yml'.
    $info = Yaml::decode(file_get_contents(drupal_get_path('module', 'yamlform') . '/yamlform.libraries.make.yml'));
    $libraries_info = $info['libraries'];
    foreach ($libraries_info as $library_name => $library_info) {
      if (isset($libraries[$library_name])) {
        $libraries[$library_name]['name'] = $library_name;
        $libraries[$library_name] += $library_info;
      }
    }
    return $libraries;
  }

}
