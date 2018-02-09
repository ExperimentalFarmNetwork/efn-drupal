<?php

namespace Drupal\yamlform_to_webform\Form;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\yamlform_to_webform\YamlFormToWebformMigrateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * YAML Form to Webform migrate form.
 */
class YamlFormToWebformMigrateForm extends ConfirmFormBase {

  /**
   * The YAML Form to Webform migrate manager.
   *
   * @var \Drupal\yamlform_to_webform\YamlFormToWebformMigrateManager
   */
  protected $migrateManager;

  /**
   * Constructs a new YamlFormToWebformMigrateForm.
   *
   * @param \Drupal\yamlform_to_webform\YamlFormToWebformMigrateManagerInterface $migrate_manager
   *   The YAML Form to Webform migrate manager.
   */
  public function __construct(YamlFormToWebformMigrateManagerInterface $migrate_manager) {
    $this->migrateManager = $migrate_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yamlform_to_webform.migrate_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yamlform_to_webform_migrate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to migrate from YAML Form 8.x-1.x to Webform 8.x-5.x?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $build = [
      'description' => [
        '#markup' => '<p>' . $this->t('This will immediately migrate all YAML Form related configuration and submissions to the Webform module.') . '</p>' .
        '<p>' . $this->t('The migration may take a few minutes.') . '</p>',
      ],
      'items' => [
        '#theme' => 'item_list',
        '#title' => $this->t('After the migration has completed, please review all...'),
        '#items' => [
          $this->t('Forms'),
          $this->t('Blocks'),
          $this->t('Content types'),
          $this->t('Fields'),
          $this->t('Links'),
          $this->t('etc...'),
        ],
      ],
    ];
    return \Drupal::service('renderer')->render($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Migrate');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('entity.yamlform.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($requirements = $this->migrateManager->requirements()) {
      $build = [
        'title' => ['#markup' => $this->t('Please review the below requirements')],
        'requirements' => [
          '#theme' => 'item_list',
          '#items' => $requirements,
        ],
      ];
      drupal_set_message($build, 'error');
      return [];
    }

    if ($this->getRequest()->getMethod() == 'GET') {
      drupal_set_message($this->t('Please make sure to test and <a href="https://www.drupal.org/docs/7/backing-up-and-migrating-a-site">backup your site</a>. <strong>This cannot be undone.</strong>'), 'warning');
    }

    $form = parent::buildForm($form, $form_state);
    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I will thoroughly test this migration and back up my site.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->migrateManager->migrate();

    // DEBUG:
    /*
    $messages = $this->migrateManager->migrate();
    $build = [
      'database' => [
        '#theme' => 'item_list',
        '#items' => $messages,
        '#title' => $this->t('Database changes'),
      ],
    ];
    drupal_set_message($build);
    */

    // Clear Drupal's cache via a new request.
    // @see drush_cache_rebuild()
    $autoloader = require DRUPAL_ROOT . '/autoload.php';
    require_once DRUPAL_ROOT . '/core/includes/utility.inc';
    $request = Request::createFromGlobals();
    DrupalKernel::bootEnvironment();
    $root = DRUPAL_ROOT;
    $site_path = DrupalKernel::findSitePath($request);
    Settings::initialize($root, $site_path, $autoloader);
    drupal_rebuild($autoloader, $request);

    // Redirect the new Webform page using a simple header redirect because
    // Drupal routing has not been updated.
    drupal_set_message($this->t("YAML Form to Webform migration has completed. Please review your forms and entire site."));
    global $base_url;
    header("Location: {$base_url}/admin/structure/webform");
    die();
  }

}
