<?php

namespace Drupal\siteimprove\Plugin\SiteimproveDomain;

use Drupal\Core\Form\FormStateInterface;
use Drupal\siteimprove\Plugin\SiteimproveDomainBase;
use Drupal\siteimprove\Plugin\SiteimproveDomainInterface;

/**
 * Provides simple plugin instance of Siteimprove Domain settings.
 *
 * @SiteimproveDomain(
 *   id = "siteimprovedomain_simple",
 *   label = @Translation("Default domain"),
 *   description = @Translation("Don't set anything and use Drupal's default settings"),
 * )
 */
class Simple extends SiteimproveDomainBase implements SiteimproveDomainInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface &$form_state, $plugin_definition) {
    parent::buildForm($form, $form_state, $plugin_definition);

    $form[$plugin_definition['id']]['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t("This plugin doesn't contain any settings."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
