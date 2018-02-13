<?php

/**
 * @file
 * Form help.
 */

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Implements hook_help().
 */
function yamlform_help($route_name, RouteMatchInterface $route_match) {
  $build = [];

  if ($route_name == 'help.page.yamlform') {
    // About.
    $build['about'] = [
      'title' => [
        '#markup' => t('About'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . t('The YAML Form module is a form builder and submission manager for Drupal 8.'),
      ],
    ];

    // Uses.
    $build['uses'] = [
      'title' => [
        '#markup' => t('Uses'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ],
    ];
    $help = _yamlform_help();
    foreach ($help as $id => $info) {
      // Title.
      $build['uses']['content'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($info['url'])) {
        $build['uses']['content'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $info['url'],
          '#title' => $info['title'],
        ];
      }
      else {
        $build['uses']['content'][$id]['title']['#markup'] = $info['title'];
      }
      // Content.
      $build['uses']['content'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'yamlform_help',
          '#info' => $info,
        ],
      ];
    }

    // Libraries.
    $build['libraries'] = [
      'title' => [
        '#markup' => t('External Libraries'),
        '#prefix' => '<h3>',
        '#suffix' => '</h3>',
      ],
      'description' => [
        '#markup' => '<p>' . t('The YAML Form module utilizes the third-party Open Source libraries listed below to enhance form elements and to provide additional functionality. It is recommended that these libraries be installed in your Drupal installations /libraries directory. If these libraries are not installed, they are automatically loaded from a CDN.') . '</p>' .
          '<p>' . t('Currently the best way to download all the needed third party libraries is to either add yamlform.libraries.make.yml to your drush make file or execute the below drush command from the root of your Drupal installation.') . '</p>' .
          '<pre>drush yamlform-libraries-download</pre>',
      ],
      'content' => [
        '#prefix' => '<dl>',
        '#suffix' => '</dl>',
      ],
      'info'  => [],
    ];
    /** @var \Drupal\yamlform\YamlFormLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('yamlform.libraries_manager');
    $libraries = $libraries_manager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $build['libraries']['content'][$library_name] = [
        'title' => [
          '#type' => 'link',
          '#title' => $library['title'],
          '#url' => $library['url'],
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ],
        'description' => [
          '#markup' => $library['description'] . '<br/><em>(' . $library['notes'] . ')</em>',
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ],
      ];
    }

  }
  else {
    // Get path from route match.
    $path = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', Url::fromRouteMatch($route_match)->setAbsolute(FALSE)->toString());

    if (strpos($route_name, 'yamlform') === FALSE && strpos($path, '/yamlform') === FALSE) {
      return NULL;
    }

    /** @var \Drupal\Core\Path\PathMatcherInterface $path_matcher */
    $path_matcher = \Drupal::service('path.matcher');

    $help = _yamlform_help();
    $build = [];
    foreach ($help as $id => $info) {
      // Set default values.
      $info += [
        'routes' => [],
        'paths' => [],
        'youtube_id' => '',
      ];

      $is_route_match = in_array($route_name, $info['routes']);
      $is_path_match = ($info['paths'] && $path_matcher->matchPath($path, implode("\n", $info['paths'])));
      if ($is_route_match || $is_path_match) {
        $build[$id] = [
          '#theme' => 'yamlform_help',
          '#info' => $info,
        ];
      }
    }
  }

  if ($build) {
    $renderer = \Drupal::service('renderer');
    $config = \Drupal::config('yamlform.settings');
    $renderer->addCacheableDependency($build, $config);
    return $build;
  }
  else {
    return NULL;
  }
}

/**
 * Get an associative array for all form help.
 *
 * @return array
 *   An associative array with all form help.
 */
function _yamlform_help() {
  $help = [];

  // Overview.
  $help['yamlform'] = [
    'title' => t('The YAML Form module'),
    'content' => t('The YAML Form module is a FAPI based form builder and submission manager for Drupal 8.'),
    'youtube_id' => '9jSOOEpzAy8',
  ];

  /****************************************************************************/
  // General.
  /****************************************************************************/

  // Forms.
  $help['forms'] = [
    'routes' => [
      // @see /admin/structure/yamlform
      'entity.yamlform.collection',
    ],
    'title' => t('Managing forms'),
    'url' => Url::fromRoute('entity.yamlform.collection'),
    'content' => t('The Forms page lists all available forms, which can be filtered by title, description, and/or elements.'),
    'youtube_id' => 'QyVytonGeH8',
  ];

  // Templates.
  if (\Drupal::moduleHandler()->moduleExists('yamlform_templates')) {
    $help['templates'] = [
      'routes' => [
        // @see /admin/structure/yamlform/templates
        'entity.yamlform.templates',
      ],
      'title' => t('Using templates'),
      'url' => Url::fromRoute('entity.yamlform.templates'),
      'content' => t('The Templates page lists reusable templates that can be duplicated and customized to create new forms.'),
      'youtube_id' => 'tvMCqC-H0bI',
    ];
  }

  // Results.
  $help['results'] = [
    'routes' => [
      // @see /admin/structure/yamlform/results/manage
      'entity.yamlform_submission.collection',
    ],
    'title' => t('Managing results'),
    'url' => Url::fromRoute('entity.yamlform_submission.collection'),
    'content' => t('The Results page lists all incoming submissions for all forms.'),
    'youtube_id' => 'EME1HoYTmVA',
  ];

  // Settings.
  $help['settings'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings
      'yamlform.settings',
    ],
    'title' => t('Defining default settings'),
    'url' => Url::fromRoute('yamlform.settings'),
    'content' => t('The Settings page allows administrators to manage global form and UI configuration settings, including updating default labels & descriptions, settings default format, and defining test dataset.'),
    'youtube_id' => 'UWxlfu7PEQg',
  ];

  // Options.
  $help['options'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings/options/manage
      'entity.yamlform_options.collection',
    ],
    'title' => t('Defining options'),
    'url' => Url::fromRoute('entity.yamlform_options.collection'),
    'content' => t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.'),
    'youtube_id' => 'vrL_TR8aQJo',
  ];

  // Elements.
  $help['elements'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings/elements
      'yamlform.element_plugins',
    ],
    'title' => t('Form element plugins'),
    'url' => Url::fromRoute('yamlform.element_plugins'),
    'content' => t('The Elements page lists all available form element plugins.') . ' ' .
    t('Form element plugins are used to enhance existing render/form elements. Form element plugins provide default properties, data normalization, custom validation, element configuration form, and customizable display formats.'),
    'youtube_id' => 'WSNGzJwnpeQ',
  ];

  // Handlers.
  $help['handlers'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings/handlers
      'yamlform.handler_plugins',
    ],
    'title' => t('Form handler plugins'),
    'url' => Url::fromRoute('yamlform.handler_plugins'),
    'content' => t('The Handlers page lists all available form handler plugins.') . ' ' .
    t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
    'youtube_id' => 'v5b4sOsUtn4',
  ];

  // Exporters.
  $help['exporters'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings/exporters
      'yamlform.exporter_plugins',
    ],
    'title' => t('Results exporter plugins'),
    'url' => Url::fromRoute('yamlform.exporter_plugins'),
    'content' => t('The Exporters page lists all available results exporter plugins.') . ' ' .
      t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
    'youtube_id' => '',
  ];

  // Third party settings.
  $help['third_party'] = [
    'routes' => [
      // @see /admin/structure/yamlform/settings/third-party
      'yamlform.admin_settings.third_party',
    ],
    'title' => t('Configuring global third party settings'),
    'url' => Url::fromRoute('yamlform.admin_settings.third_party'),
    'content' => t('The Third party settings page allows contrib and custom modules to define global settings that are applied to all forms and submissions.'),
    'youtube_id' => 'kuguydtCWf0',
  ];

  // Addons.
  $help['addons'] = [
    'routes' => [
      // @see /admin/structure/yamlform/addons
      'yamlform.addons',
    ],
    'title' => t('Extend the YAML Form module'),
    'url' => Url::fromRoute('yamlform.addons'),
    'content' => t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the YAML Form module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">YAML Form module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/yamlform']),
    'youtube_id' => '',
  ];


  /****************************************************************************/
  // Form.
  /****************************************************************************/

  // Form elements.
  $help['form_elements'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}
      'entity.yamlform.edit_form',
    ],
    'title' => t('Building a form'),
    'content' => t('The Form elements page allows users to add, update, duplicate, and delete form elements and wizard pages.'),
    'youtube_id' => 'OaQkqeJPu4M',
  ];

  // Form source.
  $help['form_source'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/source
      'entity.yamlform.source_form',
    ],
    'title' => t('Editing YAML source'),
    'content' => t("The (View) Source page allows developers to edit a form's render array using YAML markup.") . ' ' .
    t("Developers can use the (View) Source page to quickly alter a form's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
    'youtube_id' => 'BQS5YdUWo5k',
  ];

  // Form test.
  $help['form_test'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/test
      'entity.yamlform.test',
      // @see /node/{node}/yamlform/test
      'entity.node.yamlform.test',
    ],
    'title' => t('Testing a form'),
    'content' => t("The Form test page allows a form to be tested using a customizable test dataset.") . ' ' .
    t('Multiple test submissions can be created using the devel_generate module.'),
    'youtube_id' => 'PWwV7InvYmU',
  ];

  // Form settings.
  $help['form_settings'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/settings
      'entity.yamlform.settings_form',
    ],
    'title' => t('Customizing form settings'),
    'content' => t("The Form settings page allows a form's labels, messaging, and behaviors to be customized.") . ' ' .
    t('Administrators can open/close a form, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
    'youtube_id' => 'g2RWTj7XrQo',
  ];

  // Form assets.
  $help['form_assets'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/assets
      'entity.yamlform.assets_form',
    ],
    'title' => t('Adding custom CSS/JS to a form.'),
    'content' => t("The Form assets page allows site builders to attach custom CSS and JavaScript to a form."),
    // 'youtube_id' => '',
  ];

  // Form access controls.
  $help['form_access'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/access
      'entity.yamlform.access_form',
    ],
    'title' => t('Controlling access to submissions'),
    'content' => t('The Form access control page allows administrator to determine who can create, update, delete, and purge form submissions.'),
    'youtube_id' => 'xRlA1k5m09E',
  ];

  // Form handlers.
  $help['form_handlers'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/handlers
      'entity.yamlform.handlers_form',
    ],
    'title' => t('Enabling form handlers'),
    'content' => t('The Form handlers page lists additional handlers (aka behaviors) that can process form submissions.') . ' ' .
    t('Handlers are <a href=":href">plugins</a> that act on a form submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
    t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the YAML Form module.'),
    'youtube_id' => 'bZ8WDjmVFz4',
  ];

  // Form third party settings.
  $help['form_third_party'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/third_party
      'entity.yamlform.third_party_settings_form',
    ],
    'title' => t('Configuring third party settings'),
    'content' => t('The Third party settings page allows contrib and custom modules to define form specific customization settings.'),
    'youtube_id' => 'Kq3Sor1b-fI',
  ];

  // Form translations.
  $help['form_translations'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/translate
      'entity.yamlform.config_translation_overview',
    ],
    'title' => t('Translating a form'),
    'content' => t("The Translation page allows a form's configuration and elements to be translated into multiple languages."),
    // 'youtube_id' => '7nQuIpQ1pnE',
  ];

  /****************************************************************************/
  // Results.
  /****************************************************************************/

  // Form results.
  $help['form_results'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/results/submissions
      'entity.yamlform.results_submissions',
      // @see /node/{node}/yamlform/results/submissions
      'entity.node.yamlform.results_submissions',
    ],
    'title' => t('Managing results'),
    'content' => t("The Results page displays an overview of a form's submissions.") . ' ' .
    t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
    'youtube_id' => 'f1FYULMreA4',
  ];

  // Form results.
  $help['form_table'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/results/table
      'entity.yamlform.results_table',
      // @see /node/{node}/yamlform/results/table
      'entity.node.yamlform.results_table',
    ],
    'title' => t('Building a custom report'),
    'content' => t("The Table page provides a customizable table of a form's submissions. This page can be used to generate a customized report."),
    'youtube_id' => '-Y_8eUlvo8k',
  ];

  // Form download.
  $help['form_download'] = [
    'routes' => [
      // @see /admin/structure/yamlform/manage/{yamlform}/results/download
      'entity.yamlform.results_export',
      // @see /node/{node}/yamlform/results/download
      'entity.node.yamlform.results_export',
    ],
    'title' => t('Downloading results'),
    'content' => t("The Download page allows a form's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
    'youtube_id' => 'xHVXjhhVtHg',
  ];

  if (\Drupal::moduleHandler()->moduleExists('yamlform_devel')) {
    // Form Export.
    $help['form_export'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/export
        'entity.yamlform.export_form',
      ],
      'title' => t('Exporting configuration'),
      'content' => t("The Export (form) page allows developers to quickly export a single form's configuration file.") . ' ' .
      t('If you run into any issues with a form, you can also attach the below configuration (without any personal information) to a new ticket in the YAML Form module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/yamlform']),
      'youtube_id' => 'ejzx4D0ldl0',
    ];
  }

  /****************************************************************************/
  // Modules
  /****************************************************************************/

  // YAML Form Node.
  $help['yamlform_node'] = [
    'paths' => [
      '/node/add/yamlform',
    ],
    'title' => t('Creating a form node'),
    'content' => t("A form node allows forms to be fully integrated into a website as nodes."),
    'youtube_id' => 'ZvuMj4fBZDs',
  ];

  // YAML Form Block.
  $help['yamlform_block'] = [
    'paths' => [
      '/admin/structure/block/add/yamlform_block/*',
    ],
    'title' => t('Creating a form block'),
    'content' => t("A form block allows a form to be placed anywhere on a website."),
    'youtube_id' => 'CkRQMS6eJII',
  ];

  foreach ($help as $id => &$info) {
    $info['id'] = $id;
  }

  return $help;
}

/**
 * Build documentation videos HTML markup.
 *
 * The below code snippet outputs the HTML markup.
 * print('<pre>' . htmlentities(_yamlform_help_doc()) .'</pre>'); exit;
 */
function _yamlform_help_doc() {

  $help = _yamlform_help();
  $html = '';
  foreach ($help as $info) {
    $info += [
      'youtube_id' => NULL,
    ];

    $title = $info['title'];
    $content = strip_tags($info['content']);
    $youtube_id = $info['youtube_id'];
    $html .= <<<EOT
### $title

$content

<div style="position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden;">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/$youtube_id" frameborder="0" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>
</div>
<br/>


EOT;

  }
  return $html;
}
