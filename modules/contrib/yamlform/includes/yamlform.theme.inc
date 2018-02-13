<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Xss;
use Drupal\yamlform\Element\YamlFormCodeMirror;
use Drupal\yamlform\YamlFormMessageManagerInterface;
use Drupal\yamlform\Utility\YamlFormTidy;
use Drupal\yamlform\Utility\YamlFormDateHelper;
use Drupal\yamlform\Utility\YamlFormDialogHelper;
use Drupal\yamlform\Utility\YamlFormElementHelper;

/**
 * Prepares variables for yamlform help.
 *
 * Default template: yamlform_help.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - title: Help title.
 *   - content: Help content.
 */
function template_preprocess_yamlform_help(array &$variables) {
  $help_info = $variables['info'];

  $variables += $help_info;

  $help = [];
  if (is_array($help_info['content'])) {
    $help['content'] = $help_info['content'];
  }
  else {
    $help['content'] = [
      '#markup' => $help_info['content'],
    ];
  }

  // Build watch video link.
  /** @var \Drupal\yamlform\YamlFormHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('yamlform.help_manager');

  // If help info load the video else this is the video info.
  $video_info = (isset($help_info['video_id'])) ? $help_manager->getVideo($help_info['video_id']) : $help_info;
  if (!empty($video_info['youtube_id'])) {
    $classes = ['button', 'button-action', 'button--small', 'button-yamlform-play'];

    $video_display = \Drupal::config('yamlform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog';
        $help['link'] = [
          '#type' => 'link',
          '#title' => t('Watch video'),
          '#url' => Url::fromRoute('yamlform.help', ['id' => str_replace('_', '-', $video_info['id'])]),
          '#attributes' => YamlFormDialogHelper::getModalDialogAttributes(1000, $classes),
          '#prefix' => ' ',
        ];
        break;

      case 'link':
        $help['link'] = [
          '#type' => 'link',
          '#title' => t('Watch video'),
          '#url' => Url::fromUri('https://youtu.be/' . $video_info['youtube_id']),
          '#attributes' => ['class' => $classes],
          '#prefix' => ' ',
        ];
        break;

      case 'hidden':
      default:
        break;
    }
  }

  $variables['help'] = $help;
}

/**
 * Prepares variables for yamlform templates.
 *
 * Default template: yamlform.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #action, #method, #attributes, #yamlform_children.
 */
function template_preprocess_yamlform(array &$variables) {
  template_preprocess_form($variables);
}

/**
 * Prepares variables for form actions templates.
 *
 * Default template: yamlform-actions.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and buttons.
 *
 * @see \Drupal\yamlform\YamlFormSubmissionForm::actionsElement
 * @see \Drupal\yamlform\YamlFormSubmissionForm::actions
 */
function template_preprocess_yamlform_actions(array &$variables) {
  $element = $variables['element'];
  // Buttons include submit, previous, next, and draft.
  foreach (Element::children($element) as $key) {
    $variables[$key] = $element[$key];
  }
}

/**
 * Prepares variables for form confirmation templates.
 *
 * Default template: yamlform-confirmation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_confirmation(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
  $yamlform = $variables['yamlform'];
  /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
  $source_entity = $variables['source_entity'];
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  /** @var \Drupal\yamlform\YamlFormMessageManagerInterface $message_manager */
  $message_manager = \Drupal::service('yamlform.message_manager');
  $message_manager->setYamlForm($yamlform);
  $message_manager->setSourceEntity($source_entity);
  $message_manager->setYamlFormSubmission($yamlform_submission);

  $settings = $yamlform->getSettings();

  // Set progress.
  if ($yamlform->getPages() && $settings['wizard_complete'] && ($settings['wizard_progress_bar'] || $settings['wizard_progress_pages'] || $settings['wizard_progress_percentage'])) {
    $variables['progress'] = [
      '#theme' => 'yamlform_progress',
      '#yamlform' => $yamlform,
      '#current_page' => 'complete',
    ];
  }

  // Set message.
  $variables['message'] = $message_manager->build(YamlFormMessageManagerInterface::SUBMISSION_CONFIRMATION);

  // Set attributes.
  $variables['attributes'] = new Attribute($settings['confirmation_attributes']);

  // Set back.
  $variables['back'] = $settings['confirmation_back'];
  $variables['back_label'] = $settings['confirmation_back_label'] ?: \Drupal::config('yamlform.settings')->get('settings.default_confirmation_back_label');
  $variables['back_attributes'] = new Attribute($settings['confirmation_back_attributes']);

  // Apply all passed query string parameters to the 'Back to form' link.
  $query = \Drupal::request()->query->all();
  unset($query['yamlform_id']);
  $options = ($query) ? ['query' => $query] : [];

  // Set back_url.
  if ($source_entity) {
    $variables['back_url'] = $source_entity->toUrl('canonical', $options)->toString();
  }
  elseif ($yamlform_submission) {
    $variables['back_url'] = $yamlform_submission->getSourceUrl()->toString();
  }
  else {
    $variables['back_url'] = $yamlform->toUrl('canonical', $options)->toString();
  }
}

/**
 * Prepares variables for form submission navigation templates.
 *
 * Default template: yamlform-submission-navigation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 *   - rel: Form submission link template.
 *          (canonical, edit-form, resend-form, html, text, or yaml).
 */
function template_preprocess_yamlform_submission_navigation(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormRequestInterface $request_handler */
  $request_handler = \Drupal::service('yamlform.request');
  /** @var \Drupal\yamlform\YamlFormSubmissionStorageInterface $yamlform_submission_storage */
  $yamlform_submission_storage = \Drupal::entityTypeManager()->getStorage('yamlform_submission');

  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  // Get the route name, parameters, and source entity for the current page.
  // This ensures that the user stays within their current context as they are
  // paging through submission.
  $route_name = \Drupal::routeMatch()->getRouteName();
  $route_parameters = \Drupal::routeMatch()->getRawParameters()->all();
  $source_entity = $request_handler->getCurrentSourceEntity(['yamlform_submission']);

  if (strpos(\Drupal::routeMatch()->getRouteName(), 'yamlform.user.submission') !== FALSE) {
    $account = \Drupal::currentUser();
  }
  else {
    $account = NULL;
  }

  if ($previous_submission = $yamlform_submission_storage->getPreviousSubmission($yamlform_submission, $source_entity, $account)) {
    $variables['prev_url'] = Url::fromRoute($route_name, ['yamlform_submission' => $previous_submission->id()] + $route_parameters)->toString();
  }
  if ($next_submission = $yamlform_submission_storage->getNextSubmission($yamlform_submission, $source_entity, $account)) {
    $variables['next_url'] = Url::fromRoute($route_name, ['yamlform_submission' => $next_submission->id()] + $route_parameters)->toString();
  }

  $variables['#attached']['library'][] = 'yamlform/yamlform.navigation';
}

/**
 * Prepares variables for form submission information template.
 *
 * Default template: yamlform-submission-information.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_submission_information(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];
  $yamlform = $yamlform_submission->getYamlForm();

  $variables['yamlform_id'] = $yamlform->id();
  $variables['serial'] = $yamlform_submission->serial();
  $variables['sid'] = $yamlform_submission->id();
  $variables['uuid'] = $yamlform_submission->uuid();
  $variables['is_draft'] = $yamlform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $yamlform_submission->getCurrentPageTitle();
  $variables['remote_addr'] = $yamlform_submission->getRemoteAddr();
  $variables['submitted_by'] = $yamlform_submission->getOwner()->toLink();
  $variables['form'] = $yamlform->toLink();
  $variables['created'] = YamlFormDateHelper::format($yamlform_submission->getCreatedTime());
  $variables['completed'] = YamlFormDateHelper::format($yamlform_submission->getCompletedTime());
  $variables['changed'] = YamlFormDateHelper::format($yamlform_submission->getChangedTime());
  $variables['sticky'] = $yamlform_submission->isSticky() ? t('Yes') : '';
  $variables['notes'] = $yamlform_submission->getNotes();

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $yamlform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $yamlform_submission->getSourceUrl()) {
    $variables['uri'] = Link::fromTextAndUrl($source_url->setAbsolute(FALSE)->toString(), $source_url);;
  }

  if ($yamlform->getSetting('token_update')) {
    $token_url = $yamlform_submission->getTokenUrl();
    $variables['token_update'] = Link::fromTextAndUrl($token_url->setAbsolute(FALSE)->toString(), $token_url);
  }

  if ($source_entity = $yamlform_submission->getSourceEntity()) {
    $variables['submitted_to'] = $source_entity->toLink();
  }

  $variables['submissions_view'] = FALSE;
  if ($yamlform->access('submission_view_any')) {
    $variables['submissions_view'] = TRUE;
  }
  elseif ($source_entity) {
    $entity_type = $source_entity->getEntityTypeId();
    if (\Drupal::currentUser()->hasPermission("view yamlform node submissions any $entity_type")) {
      $variables['submissions_view'] = TRUE;
    }
    elseif (\Drupal::currentUser()->hasPermission("view yamlform node submissions own $entity_type")
      && method_exists($source_entity, 'getOwnerId')
      && $source_entity->getOwnerId() == \Drupal::currentUser()->id()
    ) {
      $variables['submissions_view'] = TRUE;
    }
  }
}

/**
 * Prepares variables for form submission HTML template.
 *
 * Default template: yamlform-submission-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_submission_html(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  /** @var \Drupal\yamlform\YamlFormSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('yamlform_submission');

  $yamlform = $yamlform_submission->getYamlForm();
  $data = $yamlform_submission->getData();
  $elements = $yamlform->getElementsInitialized();
  $variables['data'] = $view_builder->buildElements($elements, $data);
}

/**
 * Prepares variables for form submission table template.
 *
 * Default template: yamlform-submission-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_submission_table(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  /** @var \Drupal\yamlform\YamlFormSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('yamlform_submission');

  $yamlform = $yamlform_submission->getYamlForm();
  $data = $yamlform_submission->getData();
  $elements = $yamlform->getElementsFlattenedAndHasValue();

  $variables['table'] = $view_builder->buildTable($elements, $data);
}

/**
 * Prepares variables for form submission plain text template.
 *
 * Default template: yamlform-submission-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_submission_text(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  $variables['sid'] = $yamlform_submission->id();
  $variables['uuid'] = $yamlform_submission->uuid();
  $variables['is_draft'] = $yamlform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $yamlform_submission->getCurrentPage();
  $variables['remote_addr'] = $yamlform_submission->getRemoteAddr();
  $variables['submitted_by'] = $yamlform_submission->getOwner()->label();
  $variables['form'] = $yamlform_submission->getYamlForm()->label();
  $variables['created'] = YamlFormDateHelper::format($yamlform_submission->getCreatedTime());
  $variables['completed'] = YamlFormDateHelper::format($yamlform_submission->getCompletedTime());
  $variables['changed'] = YamlFormDateHelper::format($yamlform_submission->getChangedTime());

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $yamlform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $yamlform_submission->getSourceUrl()) {
    $variables['uri'] = $source_url->toString();
  }

  if ($source_entity = $yamlform_submission->getSourceEntity()) {
    $variables['submitted_to'] = $source_entity->label();
  }

  /** @var \Drupal\yamlform\YamlFormSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('yamlform_submission');

  $yamlform = $yamlform_submission->getYamlForm();
  $data = $yamlform_submission->getData();
  $elements = $yamlform->getElementsInitialized();
  $variables['data'] = $view_builder->buildElements($elements, $data, [], 'text');
}

/**
 * Prepares variables for form submission YAML template.
 *
 * Default template: yamlform-submission-yaml.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform_submission: A form submission.
 */
function template_preprocess_yamlform_submission_yaml(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormSubmissionInterface $yamlform_submission */
  $yamlform_submission = $variables['yamlform_submission'];

  $data = $yamlform_submission->toArray(TRUE);
  $yaml = Yaml::encode($data);
  $yaml = YamlFormTidy::tidy($yaml);
  $variables['yaml'] = [
    '#markup' => $yaml,
    '#allowed_tags' => Xss::getAdminTagList(),
  ];;
}

/**
 * Prepares variables for form CodeMirror template.
 *
 * Default template: yamlform-codemirror.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - code: The code.
 *   - type: The type of code.
 */
function template_preprocess_yamlform_codemirror(array &$variables) {
  $variables['mode'] = YamlFormCodeMirror::getMode($variables['type']);
  if (is_string($variables['code'])) {
    $variables['code'] = [
      '#markup' => $variables['code'],
      '#allowed_tags' => Xss::getAdminTagList(),
    ];
  }
}

/**
 * Prepares variables for form element base HTML template.
 *
 * Default template: yamlform-element-base-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_yamlform_element_base_html(array &$variables) {
  $element = $variables['element'];
  $variables['title'] = (YamlFormElementHelper::isTitleDisplayed($element)) ? $element['#title'] : NULL;
}

/**
 * Prepares variables for form element base text template.
 *
 * Default template: yamlform-element-base-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_yamlform_element_base_text(array &$variables) {
  template_preprocess_yamlform_element_base_html($variables);
}

/**
 * Prepares variables for form container base HTML template.
 *
 * Default template: yamlform-container-base-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_yamlform_container_base_html(array &$variables) {
  $element = $variables['element'];
  $variables['title'] = (isset($element['#title'])) ? $element['#title'] : NULL;
  $variables['id'] = $element['#yamlform_id'];
}

/**
 * Prepares variables for form container base text template.
 *
 * Default template: yamlform-container-base-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_yamlform_container_base_text(array &$variables) {
  $element = $variables['element'];
  $variables['title'] = (isset($element['#title'])) ? $element['#title'] : NULL;
}

/**
 * Prepares variables for form 'wizard' progress template.
 *
 * Default template: yamlform-progress.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform: A form.
 *   - current_page: The current wizard page.
 */
function template_preprocess_yamlform_progress(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
  $yamlform = $variables['yamlform'];
  $current_page = $variables['current_page'];

  $pages = $yamlform->getPages();

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];

  $total = count($page_keys);

  if ($yamlform->getSetting('wizard_progress_bar')) {
    $variables['bar'] = [
      '#theme' => 'yamlform_progress_bar',
      '#yamlform' => $variables['yamlform'],
      '#current_page' => $variables['current_page'],
    ];
  }

  if ($yamlform->getSetting('wizard_progress_pages')) {
    $variables['summary'] = t('Page @start of @end', ['@start' => $current_index + 1, '@end' => $total]);
  }

  if ($yamlform->getSetting('wizard_progress_percentage')) {
    $variables['percentage'] = number_format(($current_index / ($total - 1)) * 100, 0) . '%';
  }
}

/**
 * Prepares variables for form 'wizard' progress bar template.
 *
 * Default template: yamlform-progress-bar.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - yamlform: A form.
 *   - current_page: The current wizard page.
 */
function template_preprocess_yamlform_progress_bar(array &$variables) {
  /** @var \Drupal\yamlform\YamlFormInterface $yamlform */
  $yamlform = $variables['yamlform'];
  $current_page = $variables['current_page'];

  $pages = $yamlform->getPages();

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];
  $variables['current_index'] = $current_index;

  // Reset the pages variable.
  $variables['progress'] = [];
  foreach ($pages as $page) {
    $variables['progress'][] = (isset($page['#title'])) ? $page['#title'] : '';
  }
}

/******************************************************************************/
// Element templates
/******************************************************************************/

/**
 * Prepares variables for Form message templates.
 *
 * Default template: yamlform-message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 *
 * @see template_preprocess_container()
 */
function template_preprocess_yamlform_message(&$variables) {
  $variables['has_parent'] = FALSE;
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += ['#attributes' => []];

  // Special handling for form elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    $variables['has_parent'] = TRUE;
  }

  $variables['message'] = $element['#message'];
  $variables['attributes'] = $element['#attributes'];
  if (isset($element['#closed'])) {
    $variables['closed'] = $element['#closed'];
  }
}

/******************************************************************************/
// Composite templates
/******************************************************************************/

/**
 * Prepares variables for address composite element templates.
 *
 * Default template: yamlform-element-address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_yamlform_composite_address(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for contact composite element templates.
 *
 * Default template: yamlform-element-contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_yamlform_composite_contact(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for creditcard composite element templates.
 *
 * Default template: yamlform-element-creditcard.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_yamlform_composite_creditcard(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for location composite element templates.
 *
 * Default template: yamlform-element-location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_yamlform_composite_location(array &$variables) {
  $variables['content'] = $variables['element'];
}

/**
 * Prepares variables for name composite element templates.
 *
 * Default template: yamlform-element-name.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_yamlform_composite_name(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/******************************************************************************/
// Element templates
/******************************************************************************/

/**
 * Prepares variables for a managed file element.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_yamlform_element_managed_file(array &$variables) {
  /** @var \Drupal\file\FileInterface $file */
  $file = $variables['file'];
  $variables['uri'] = file_create_url($file->getFileUri());
  $variables['extension'] = strtolower(pathinfo($variables['uri'], PATHINFO_EXTENSION));
  $variables['type'] = \Drupal::service('file.mime_type.guesser')->guess($variables['uri']);
  $variables['file_link'] = [
    '#theme' => 'file_link',
    '#file' => $file,
  ];
}

/**
 * Prepares variables for an audio file element.
 *
 * Default template: yamlform-element-audio-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_yamlform_element_audio_file(array &$variables) {
  template_preprocess_yamlform_element_managed_file($variables);
}

/**
 * Prepares variables for a document file element.
 *
 * Default template: yamlform-element-document-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_yamlform_element_document_file(array &$variables) {
  template_preprocess_yamlform_element_managed_file($variables);
}

/**
 * Prepares variables for an image file element.
 *
 * Default template: yamlform-element-image-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_yamlform_element_image_file(array &$variables) {
  template_preprocess_yamlform_element_managed_file($variables);
}

/**
 * Prepares variables for a video file element.
 *
 * Default template: yamlform-element-video-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The form element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_yamlform_element_video_file(array &$variables) {
  template_preprocess_yamlform_element_managed_file($variables);
}
