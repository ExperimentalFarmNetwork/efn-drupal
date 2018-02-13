<?php

namespace Drupal\efn_users\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a profile links lock.
 *
 * @Block(
 *   id = "efn_user_profiles",
 *   admin_label = @Translation("EFN Profile Links"),
 * )
 */
class ProfilesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

  	$currentUser = \Drupal::currentUser();
  	if ($currentUser->id()) {
	  	$user = \Drupal::routeMatch()->getParameter('user');
	  	// Terrible! Replace with twig template ASAP.
	  	$markup = '';

	  	if ($user && $currentUser->id() == $user->id()) {
		  	$volunteerProfile = \Drupal::entityManager()->getStorage('profile')
					->loadByUser($user, 'volunteer');

				if (!$volunteerProfile) {
					$markup .= '<p>';
					$markup .= t('Start joining joining projects and connecting to 
						others in the network:');

					$url = Url::fromRoute('entity.profile.type.main.user_profile_form', ['user' => $user->id(), 'profile_type' => 'volunteer']);
					$link_options = [
					  'attributes' => [
					    'class' => ['btn', 'btn-primary'],
					   ],
					];
					$url->setOptions($link_options);
					$link = Link::fromTextAndUrl(t('Create Volunteer Profile'), $url )->toString();
					$markup .= '</p>';
					$markup .= $link;
				}

			  $researcherProfile = \Drupal::entityManager()->getStorage('profile')
						->loadByUser($user, 'researcher');

				if (!$researcherProfile) {
					$markup .= '<p>';
					$markup .= t('Start creating and leading your own projects:');

					$url = Url::fromRoute('entity.profile.type.main.user_profile_form', ['user' => $user->id(), 'profile_type' => 'researcher']);
					$link_options = [
					  'attributes' => [
					    'class' => ['btn', 'btn-primary'],
					   ],
					];
					$url->setOptions($link_options);
					$link = Link::fromTextAndUrl(t('Create Researcher Profile'), $url )->toString();
					$markup .= '</p>';
					$markup .= $link;
				}

				if ($markup) {
					return array(
					  '#markup' => $markup,
					);				
				}
	  	}
	  }
 		return array();
  }
  
  public function getCacheTags() {
    if ($user = \Drupal::routeMatch()->getParameter('user')) {
      // Clear cache if user object updated
      return Cache::mergeTags(parent::getCacheTags(), array('user:' . $user->id()));
    } else {
      return parent::getCacheTags();
    }
  }

  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }
}