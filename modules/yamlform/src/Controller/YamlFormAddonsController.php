<?php

namespace Drupal\yamlform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\yamlform\YamlFormAddonsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for form add-on.
 */
class YamlFormAddonsController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The add-ons manager.
   *
   * @var \Drupal\yamlform\YamlFormAddonsManagerInterface
   */
  protected $addons;

  /**
   * Constructs a new YamlFormSubmissionController object.
   *
   * @param \Drupal\yamlform\YamlFormAddonsManagerInterface $addons
   *   The add-ons manager.
   */
  public function __construct(YamlFormAddonsManagerInterface $addons) {
    $this->addons = $addons;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('yamlform.addons_manager')
    );
  }

  /**
   * Returns the YAML Form extend page.
   *
   * @return array
   *   The form submission form.
   */
  public function index() {
    $build = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['yamlform-addons', 'js-yamlform-details-toggle', 'yamlform-details-toggle'],
      ],
    ];
    $build['#attached']['library'][] = 'yamlform/yamlform.admin';
    $build['#attached']['library'][] = 'yamlform/yamlform.element.details.toggle';

    $categories = $this->addons->getCategories();
    foreach ($categories as $category_name => $category) {
      $build[$category_name] = [
        '#type' => 'details',
        '#title' => $category['title'],
        '#open' => TRUE,
      ];
      $projects = $this->addons->getProjects($category_name);
      foreach ($projects as &$project) {
        $project['description'] .= ' ' . '<br/><small>' . $project['url']->toString() . '</small>';
      }
      $build[$category_name]['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $projects,
      ];
    }
    return $build;
  }

}
