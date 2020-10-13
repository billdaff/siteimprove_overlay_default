<?php

namespace Drupal\siteimprove\Plugin\SiteimproveDomain;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\siteimprove\Plugin\SiteimproveDomainBase;

/**
 * Provides simple plugin instance of Siteimprove Domain settings.
 *
 * @package Drupal\siteimprove\Plugin\SiteimproveDomain
 *
 * @SiteimproveDomain(
 *   id = "siteimprovedomain_single",
 *   label = @Translation("Single frontend domain"),
 *   description = @Translation("Set a single domain for Siteimprove. Useful if you have a different backend domain than frontend domain."),
 * )
 */
class Single extends SiteimproveDomainBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface &$form_state, $plugin_definition) {
    parent::buildForm($form, $form_state, $plugin_definition);

    $form[$plugin_definition['id']]['single_domain'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Input your domain name. If you leave out http:// or https://, the scheme will inherit the scheme of the web request.'),
      '#default_value' => $this->config('siteimprove.domain.single.settings')->get('domain'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $value = $form_state->getValue('single_domain');
    if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9][a-zA-Z0-9-_]*\.)*[a-zA-Z0-9]*[a-zA-Z0-9-_]*[[a-zA-Z0-9]+(:\d+)?$/', $value)) {
      $form_state->setErrorByName('single_domain', $this->t('Only use valid domain names in this field - no trailing slash, no trailing whitespace.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('siteimprove.domain.single.settings');
    $config->set('domain', $form_state->getValue('single_domain'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls(EntityBase $entity) {
    $config = $this->config('siteimprove.domain.single.settings');
    $domain = $config->get('domain');
    $scheme = preg_match('/^https?:\/\//', $domain) ? '' : \Drupal::request()->getScheme() . '://';
    return [$scheme . $domain];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['siteimprove.domain.single.settings'];
  }

}
