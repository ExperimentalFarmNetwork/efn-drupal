<?php

namespace Drupal\email_registration\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Url;

/**
 * Provides the email registration login pane.
 *
 * @CommerceCheckoutPane(
 *   id = "email_registration_login",
 *   label = @Translation("Login with email registration or continue as guest"),
 *   default_step = "_disabled",
 * )
 */
class EmailRegistrationLogin extends Login {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    $config = \Drupal::config('email_registration.settings');
    $login_with_username = $config->get('login_with_username');
    $pane_form['returning_customer']['name']['#title'] = $login_with_username ? t('Email address or username') : t('Email address');
    $pane_form['returning_customer']['name']['#description'] = $login_with_username ? t('Enter your email address or username.') : t('Enter your email address.');
    $pane_form['returning_customer']['name']['#element_validate'][] = 'email_registration_user_login_validate';
    $pane_form['returning_customer']['name']['#type'] = $login_with_username ? 'textfield' : 'email';
    $pane_form['returning_customer']['name']['#maxlength'] = Email::EMAIL_MAX_LENGTH;
    $pane_form['returning_customer']['password']['#description'] = t('Enter the password that accompanies your email address.');
    $complete_form['#cache']['tags'][] = 'config:email_registration.settings';

    $pane_form['register']['name']['#type'] = 'value';
    $pane_form['register']['name']['#value'] = 'email_registration_' . user_password();
    $pane_form['register']['mail']['#title'] = t('Email');

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#op'] === 'login') {
      $mail = $form_state->getValue([
        'email_registration_login',
        'returning_customer',
        'name',
      ]);
      if (!empty($mail)) {
        $config = \Drupal::config('email_registration.settings');
        if ($user = user_load_by_mail($mail)) {
          $username = $user->getAccountName();
          $form_state->setValue([
            'email_registration_login',
            'returning_customer',
            'name',
          ], $username);
        }
        elseif (!$config->get('login_with_username')) {
          $user_input = $form_state->getUserInput();
          $query = isset($user_input['email_registration_login']['returning_customer']['name']) ? ['name' => $user_input['email_registration_login']['returning_customer']['name']] : [];
          $form_state->setError($pane_form['returning_customer'], t('Unrecognized email address or password. <a href=":password">Forgot your password?</a>', [
            ':password' => Url::fromRoute('user.pass', [], ['query' => $query])
              ->toString(),
          ]));
          return;
        }
      }
      $name_element = $pane_form['returning_customer']['name'];
      $password = trim($values['returning_customer']['password']);
      // Generate the "reset password" url.
      $query = !empty($username) ? ['name' => $username] : [];
      $password_url = Url::fromRoute('user.pass', [], ['query' => $query])
        ->toString();

      if (user_is_blocked($username)) {
        $form_state->setError($name_element, $this->t('The account with email address %mail has not been activated or is blocked.', ['%mail' => $mail]));
        return;
      }
      if (!$this->credentialsCheckFlood->isAllowedHost($this->clientIp)) {
        $form_state->setErrorByName($name_element, $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')]));
        $this->credentialsCheckFlood->register($this->clientIp, $username);
        return;
      }
      elseif (!$this->credentialsCheckFlood->isAllowedAccount($this->clientIp, $username)) {
        $form_state->setErrorByName($name_element, $this->t('Too many failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => Url::fromRoute('user.pass')]));
        $this->credentialsCheckFlood->register($this->clientIp, $username);
        return;
      }

      $uid = $this->userAuth->authenticate($username, $password);
      if (!$uid) {
        $this->credentialsCheckFlood->register($this->clientIp, $username);
        $form_state->setError($name_element, $this->t('Unrecognized email address or password. <a href=":password">Forgot your password?</a>', [':url' => $password_url]));
      }
      $form_state->set('logged_in_uid', $uid);
    }
    parent::validatePaneForm($pane_form, $form_state, $complete_form);
  }

}
