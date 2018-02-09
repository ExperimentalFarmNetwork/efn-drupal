<?php

namespace Drupal\yamlform;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Url;
use Drupal\yamlform\Element\YamlFormMessage;

/**
 * Form help manager.
 */
class YamlFormHelpManager implements YamlFormHelpManagerInterface {

  use StringTranslationTrait;

  /**
   * Help for the YAML Form module.
   *
   * @var array
   */
  protected $help;

  /**
   * Videos for the YAML Form module.
   *
   * @var array
   */
  protected $videos;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The YAML Form add-ons manager.
   *
   * @var \Drupal\yamlform\YamlFormAddOnsManagerInterface
   */
  protected $addOnsManager;

  /**
   * The YAML Form libraries manager.
   *
   * @var \Drupal\yamlform\YamlFormLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * Constructs a YamlFormHelpManager object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\yamlform\YamlFormAddOnsManagerInterface $addons_manager
   *   The YAML Form add-ons manager.
   * @param \Drupal\yamlform\YamlFormLibrariesManagerInterface $libraries_manager
   *   The YAML Form libraries manager.
   */
  public function __construct(AccountInterface $current_user, ModuleHandlerInterface $module_handler, StateInterface $state, PathMatcherInterface $path_matcher, YamlFormAddOnsManagerInterface $addons_manager, YamlFormLibrariesManagerInterface $libraries_manager) {
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->state = $state;
    $this->pathMatcher = $path_matcher;
    $this->addOnsManager = $addons_manager;
    $this->librariesManager = $libraries_manager;

    $this->help = $this->initHelp();
    $this->videos = $this->initVideos();
  }

  /**
   * {@inheritdoc}
   */
  public function getHelp($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->help[$id])) ? $this->help[$id] : NULL;
    }
    else {
      return $this->help;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVideo($id = NULL) {
    if ($id !== NULL) {
      return (isset($this->videos[$id])) ? $this->videos[$id] : NULL;
    }
    else {
      return $this->videos;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHelp($route_name, RouteMatchInterface $route_match) {
    // Get path from route match.
    $path = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', Url::fromRouteMatch($route_match)->setAbsolute(FALSE)->toString());

    $build = [];
    foreach ($this->help as $id => $help) {
      // Set default values.
      $help += [
        'routes' => [],
        'paths' => [],
        'access' => TRUE,
        'message_type' => '',
        'message_close' => FALSE,
        'message_id' => '',
        'message_storage' => '',
        'video_id' => '',
      ];

      if (!$help['access']) {
        continue;
      }

      $is_route_match = in_array($route_name, $help['routes']);
      $is_path_match = ($help['paths'] && $this->pathMatcher->matchPath($path, implode("\n", $help['paths'])));
      $has_help = ($is_route_match || $is_path_match);
      if (!$has_help) {
        continue;
      }

      if ($help['message_type']) {
        $build[$id] = [
          '#type' => 'yamlform_message',
          '#message_type' => $help['message_type'],
          '#message_close' => $help['message_close'],
          '#message_id' => ($help['message_id']) ? $help['message_id'] : 'yamlform.help.' . $help['id'],
          '#message_storage' => $help['message_storage'],
          '#message_message' => [
            '#theme' => 'yamlform_help',
            '#info' => $help,
          ],
        ];
        if ($help['message_close']) {
          $build['#cache']['max-age'] = 0;
        }
      }
      else {
        $build[$id] = [
          '#theme' => 'yamlform_help',
          '#info' => $help,
        ];
      }

    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildIndex() {
    $build = [
      '#prefix' => '<div class="yamlform-help-accordion">',
      '#suffix' => '</div>',
    ];
    $build['about'] = $this->buildAbout();
    $build['uses'] = $this->buildUses();
    $build['libraries'] = $this->buildLibraries();
    $build['#attached']['library'][] = 'yamlform/yamlform.help';
    return $build;
  }

  /****************************************************************************/
  // Index sections.
  /****************************************************************************/

  /**
   * Build the about section.
   *
   * @return array
   *   An render array containing the about section.
   */
  protected function buildAbout() {
    return [
      'title' => [
        '#markup' => $this->t('About'),
        '#prefix' => '<h3 id="about">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#markup' => '<p>' . $this->t('The YAML Form module is a form builder and submission manager for Drupal 8.') . '</p>',
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ],
    ];
  }

  /**
   * Build the uses section.
   *
   * @return array
   *   An render array containing the uses section.
   */
  protected function buildUses() {
    $build = [
      'title' => [
        '#markup' => $this->t('Uses'),
        '#prefix' => '<h3 id="uses">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'help' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    foreach ($this->help as $id => $info) {
      // Title.
      $build['content']['help'][$id]['title'] = [
        '#prefix' => '<dt>',
        '#suffix' => '</dt>',
      ];
      if (isset($info['url'])) {
        $build['content']['help'][$id]['title']['link'] = [
          '#type' => 'link',
          '#url' => $info['url'],
          '#title' => $info['title'],
        ];
      }
      else {
        $build['content']['help'][$id]['title']['#markup'] = $info['title'];
      }
      // Content.
      $build['content']['help'][$id]['content'] = [
        '#prefix' => '<dd>',
        '#suffix' => '</dd>',
        'content' => [
          '#theme' => 'yamlform_help',
          '#info' => $info,
        ],
      ];
    }
    return $build;
  }

  /**
   * Build the libraries section.
   *
   * @return array
   *   An render array containing the libraries section.
   */
  protected function buildLibraries() {
    // Libraries.
    $build = [
      'title' => [
        '#markup' => $this->t('External Libraries'),
        '#prefix' => '<h3 id="libraries">',
        '#suffix' => '</h3>',
      ],
      'content' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'description' => [
          '#markup' => '<p>' . $this->t('The YAML Form module utilizes the third-party Open Source libraries listed below to enhance form elements and to provide additional functionality. It is recommended that these libraries be installed in your Drupal installations /libraries directory. If these libraries are not installed, they are automatically loaded from a CDN.') . '</p>' .
            '<p>' . $this->t('Currently the best way to download all the needed third party libraries is to either add <a href=":href">yamlform.libraries.make.yml</a> to your drush make file or execute the below drush command from the root of your Drupal installation.', [':href' => 'http://cgit.drupalcode.org/yamlform/tree/yamlform.libraries.make.yml']) . '</p>' .
            '<hr/><pre>drush yamlform-libraries-download</pre><hr/><br/>',
        ],
        'libraries' => [
          '#prefix' => '<dl>',
          '#suffix' => '</dl>',
        ],
      ],
    ];
    $libraries = $this->librariesManager->getLibraries();
    foreach ($libraries as $library_name => $library) {
      $build['content']['libraries'][$library_name] = [
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
    return $build;
  }

  /**
   * Initialize videos.
   *
   * @return array
   *   An associative array containing videos.
   */
  protected function initVideos() {
    return $this->help;
  }

  /**
   * Initialize help.
   *
   * @return array
   *   An associative array containing help.
   */
  protected function initHelp() {
    $help = [];

    // Install.
    $t_args = [
      ':addons_href' => Url::fromRoute('yamlform.addons')->toString(),
      ':submodules_href' => Url::fromRoute('system.modules_list', [], ['fragment' => 'edit-modules-yaml-form'])->toString(),
      ':libraries_href' => Url::fromRoute('help.page', ['name' => 'yamlform'], ['fragment' => 'libraries'])->toString(),
    ];
    $help['install'] = [
      'routes' => [
        // @see /admin/modules
        'system.modules_list',
      ],
      'title' => $this->t('Installing the YAML Form module'),
      'content' => $this->t('<strong>Congratulations!</strong> You have successfully installed the YAML Form module. Please make sure to install additional <a href=":libraries_href">third-party libraries</a>, <a href=":submodules_href">sub-modules</a>, and optional <a href=":addons_href">add-ons</a>.', $t_args),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => YamlFormMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer yamlform'),
      // 'youtube_id' => 'install',
    ];

    // Release.
    $module_info = Yaml::decode(file_get_contents($this->moduleHandler->getModule('yamlform')->getPathname()));
    $version = isset($module_info['version']) ? $module_info['version'] : '8.x-1.x-dev';
    $installed_version = $this->state->get('yamlform.version');
    // Reset storage state if the version has changed.
    if ($installed_version != $version) {
      YamlFormMessage::resetClosed(YamlFormMessage::STORAGE_STATE, 'yamlform.help.release');
      $this->state->set('yamlform.version', $version);
    }
    $t_args = [
      '@version' => $version,
      ':href' => 'https://www.drupal.org/project/yamlform/releases/' . $version,
    ];
    $help['release'] = [
      'routes' => [
        // @see /admin/structure/yamlform
        'entity.yamlform.collection',
      ],
      'title' => $this->t('You have successfully updated...'),
      'content' => $this->t('You have successfully updated to the @version release of the YAML Form module. <a href=":href">Learn more</a>', $t_args),
      'message_type' => 'status',
      'message_close' => TRUE,
      'message_storage' => YamlFormMessage::STORAGE_STATE,
      'access' => $this->currentUser->hasPermission('administer yamlform'),
    ];

    // Introduction.
    $help['introduction'] = [
      'routes' => [
        // @see /admin/structure/yamlform
        'entity.yamlform.collection',
      ],
      'title' => $this->t('It is time to say goodbye...'),
      'content' => $this->t('It is time to say goodbye to the YAML Form module and migrate to the Webform module.'),
      'message_type' => 'info',
      'message_close' => TRUE,
      'message_storage' => YamlFormMessage::STORAGE_USER,
      'access' => $this->currentUser->hasPermission('administer yamlform'),
      'youtube_id' => 'GKzPSHAiqgU',
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
      'title' => $this->t('Managing forms'),
      'url' => Url::fromRoute('entity.yamlform.collection'),
      'content' => $this->t('The Forms page lists all available forms, which can be filtered by title, description, and/or elements.'),
      'youtube_id' => 'QyVytonGeH8',
    ];

    // Templates.
    if ($this->moduleHandler->moduleExists('yamlform_templates')) {
      $help['templates'] = [
        'routes' => [
          // @see /admin/structure/yamlform/templates
          'entity.yamlform.templates',
        ],
        'title' => $this->t('Using templates'),
        'url' => Url::fromRoute('entity.yamlform.templates'),
        'content' => $this->t('The Templates page lists reusable templates that can be duplicated and customized to create new forms.'),
        'youtube_id' => 'tvMCqC-H0bI',
      ];
    }

    // Results.
    $help['results'] = [
      'routes' => [
        // @see /admin/structure/yamlform/results/manage
        'entity.yamlform_submission.collection',
      ],
      'title' => $this->t('Managing results'),
      'url' => Url::fromRoute('entity.yamlform_submission.collection'),
      'content' => $this->t('The Results page lists all incoming submissions for all forms.'),
      'youtube_id' => 'EME1HoYTmVA',
    ];

    // Settings.
    $help['settings'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings
        'yamlform.settings',
      ],
      'title' => $this->t('Defining default settings'),
      'url' => Url::fromRoute('yamlform.settings'),
      'content' => $this->t('The Settings page allows administrators to manage global form and UI configuration settings, including updating default labels & descriptions, settings default format, and defining test dataset.'),
      'youtube_id' => 'UWxlfu7PEQg',
    ];

    // Options.
    $help['options'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings/options/manage
        'entity.yamlform_options.collection',
      ],
      'title' => $this->t('Defining options'),
      'url' => Url::fromRoute('entity.yamlform_options.collection'),
      'content' => $this->t('The Options page lists predefined options which are used to build select menus, radio buttons, checkboxes and likerts.'),
      'youtube_id' => 'vrL_TR8aQJo',
    ];

    // Elements.
    $help['elements'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings/elements
        'yamlform.element_plugins',
      ],
      'title' => $this->t('Form element plugins'),
      'url' => Url::fromRoute('yamlform.element_plugins'),
      'content' => $this->t('The Elements page lists all available form element plugins.') . ' ' .
        $this->t('Form element plugins are used to enhance existing render/form elements. Form element plugins provide default properties, data normalization, custom validation, element configuration form, and customizable display formats.'),
      'youtube_id' => 'WSNGzJwnpeQ',
    ];

    // Handlers.
    $help['handlers'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings/handlers
        'yamlform.handler_plugins',
      ],
      'title' => $this->t('Form handler plugins'),
      'url' => Url::fromRoute('yamlform.handler_plugins'),
      'content' => $this->t('The Handlers page lists all available form handler plugins.') . ' ' .
        $this->t('Handlers are used to route submitted data to external applications and send notifications & confirmations.'),
      'youtube_id' => 'v5b4sOsUtn4',
    ];

    // Exporters.
    $help['exporters'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings/exporters
        'yamlform.exporter_plugins',
      ],
      'title' => $this->t('Results exporter plugins'),
      'url' => Url::fromRoute('yamlform.exporter_plugins'),
      'content' => $this->t('The Exporters page lists all available results exporter plugins.') . ' ' .
        $this->t('Exporters are used to export results into a downloadable format that can be used by MS Excel, Google Sheets, and other spreadsheet applications.'),
      'youtube_id' => '',
    ];

    // Third party settings.
    $help['third_party'] = [
      'routes' => [
        // @see /admin/structure/yamlform/settings/third-party
        'yamlform.admin_settings.third_party',
      ],
      'title' => $this->t('Configuring global third party settings'),
      'url' => Url::fromRoute('yamlform.admin_settings.third_party'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define global settings that are applied to all forms and submissions.'),
      'youtube_id' => 'kuguydtCWf0',
    ];

    // Addons.
    $help['addons'] = [
      'routes' => [
        // @see /admin/structure/yamlform/addons
        'yamlform.addons',
      ],
      'title' => $this->t('Extend the YAML Form module'),
      'url' => Url::fromRoute('yamlform.addons'),
      'content' => $this->t('The Add-ons page includes a list of modules and projects that extend and/or provide additional functionality to the YAML Form module and Drupal\'s Form API.  If you would like a module or project to be included in the below list, please submit a request to the <a href=":href">YAML Form module\'s issue queue</a>.', [':href' => 'https://www.drupal.org/node/add/project-issue/yamlform']),
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
      'title' => $this->t('Building a form'),
      'content' => $this->t('The Form elements page allows users to add, update, duplicate, and delete form elements and wizard pages.'),
      'youtube_id' => 'OaQkqeJPu4M',
    ];

    // Form source.
    $help['form_source'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/source
        'entity.yamlform.source_form',
      ],
      'title' => $this->t('Editing YAML source'),
      'content' => $this->t("The (View) Source page allows developers to edit a form's render array using YAML markup.") . ' ' .
        $this->t("Developers can use the (View) Source page to quickly alter a form's labels, cut-n-paste multiple elements, reorder elements, and add customize properties and markup to elements."),
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
      'title' => $this->t('Testing a form'),
      'content' => $this->t("The Form test page allows a form to be tested using a customizable test dataset.") . ' ' .
        $this->t('Multiple test submissions can be created using the devel_generate module.'),
      'youtube_id' => 'PWwV7InvYmU',
    ];

    // Form settings.
    $help['form_settings'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/settings
        'entity.yamlform.settings_form',
      ],
      'title' => $this->t('Customizing form settings'),
      'content' => $this->t("The Form settings page allows a form's labels, messaging, and behaviors to be customized.") . ' ' .
        $this->t('Administrators can open/close a form, enable/disable drafts, allow previews, set submission limits, and disable the saving of results.'),
      'youtube_id' => 'g2RWTj7XrQo',
    ];

    // Form assets.
    $help['form_assets'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/assets
        'entity.yamlform.assets_form',
      ],
      'title' => $this->t('Adding custom CSS/JS to a form.'),
      'content' => $this->t("The Form assets page allows site builders to attach custom CSS and JavaScript to a form."),
      // 'youtube_id' => '',
    ];

    // Form access controls.
    $help['form_access'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/access
        'entity.yamlform.access_form',
      ],
      'title' => $this->t('Controlling access to submissions'),
      'content' => $this->t('The Form access control page allows administrator to determine who can create, update, delete, and purge form submissions.'),
      'youtube_id' => 'xRlA1k5m09E',
    ];

    // Form handlers.
    $help['form_handlers'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/handlers
        'entity.yamlform.handlers_form',
      ],
      'title' => $this->t('Enabling form handlers'),
      'content' => $this->t('The Form handlers page lists additional handlers (aka behaviors) that can process form submissions.') . ' ' .
        $this->t('Handlers are <a href=":href">plugins</a> that act on a form submission.', [':href' => 'https://www.drupal.org/developing/api/8/plugins']) . ' ' .
        $this->t('For example, sending email confirmations and notifications is done using the Email handler which is provided by the YAML Form module.'),
      'youtube_id' => 'bZ8WDjmVFz4',
    ];

    // Form third party settings.
    $help['form_third_party'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/third_party
        'entity.yamlform.third_party_settings_form',
      ],
      'title' => $this->t('Configuring third party settings'),
      'content' => $this->t('The Third party settings page allows contrib and custom modules to define form specific customization settings.'),
      'youtube_id' => 'Kq3Sor1b-fI',
    ];

    // Form translations.
    $help['form_translations'] = [
      'routes' => [
        // @see /admin/structure/yamlform/manage/{yamlform}/translate
        'entity.yamlform.config_translation_overview',
      ],
      'title' => $this->t('Translating a form'),
      'content' => $this->t("The Translation page allows a form's configuration and elements to be translated into multiple languages."),
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
      'title' => $this->t('Managing results'),
      'content' => $this->t("The Results page displays an overview of a form's submissions.") . ' ' .
        $this->t("Submissions can be reviewed, updated, flagged, annotated, and downloaded."),
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
      'title' => $this->t('Building a custom report'),
      'content' => $this->t("The Table page provides a customizable table of a form's submissions. This page can be used to generate a customized report."),
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
      'title' => $this->t('Downloading results'),
      'content' => $this->t("The Download page allows a form's submissions to be exported in to a customizable CSV (Comma Separated Values) file."),
      'youtube_id' => 'xHVXjhhVtHg',
    ];

    if ($this->moduleHandler->moduleExists('yamlform_devel')) {
      // Form Export.
      $help['form_export'] = [
        'routes' => [
          // @see /admin/structure/yamlform/manage/{yamlform}/export
          'entity.yamlform.export_form',
        ],
        'title' => $this->t('Exporting configuration'),
        'content' => $this->t("The Export (form) page allows developers to quickly export a single form's configuration file.") . ' ' .
          $this->t('If you run into any issues with a form, you can also attach the below configuration (without any personal information) to a new ticket in the YAML Form module\'s <a href=":href">issue queue</a>.', [':href' => 'https://www.drupal.org/project/issues/yamlform']),
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
      'title' => $this->t('Creating a form node'),
      'content' => $this->t("A form node allows forms to be fully integrated into a website as nodes."),
      'youtube_id' => 'ZvuMj4fBZDs',
    ];

    // YAML Form Block.
    $help['yamlform_block'] = [
      'paths' => [
        '/admin/structure/block/add/yamlform_block/*',
      ],
      'title' => $this->t('Creating a form block'),
      'content' => $this->t("A form block allows a form to be placed anywhere on a website."),
      'youtube_id' => 'CkRQMS6eJII',
    ];

    // YAML Form to Webform.
    if ($this->moduleHandler->moduleExists('yamlform_to_webform')) {
      $help['yamlform_to_webform'] = [
        'routes' => [
          // @see /admin/structure/yamlform/migrate
          'yamlform_to_webform.migrate',
        ],
        'title' => $this->t('Migrating from YAML Form 8.x-1.x to Webform 8.x-1.x'),
        'content' => $this->t("The Migrate page will move your YAML Form configuration and modules to the Webform module."),
        'youtube_id' => 'GKzPSHAiqgU',
      ];
    }

    foreach ($help as $id => &$info) {
      $info['id'] = $id;
      // @todo Make video independent of help.
      // TEMP: Video IDs match Help IDs.
      if (!empty($info['youtube_id'])) {
        $info['video_id'] = $id;
      }
    }

    return $help;
  }

}
