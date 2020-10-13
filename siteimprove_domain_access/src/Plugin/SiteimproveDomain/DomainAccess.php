<?php

namespace Drupal\siteimprove_domain_access\Plugin\SiteimproveDomain;

use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\siteimprove\Plugin\SiteimproveDomainBase;
use Drupal\siteimprove\Plugin\SiteimproveDomainInterface;

/**
 * Provides simple plugin instance of Siteimprove Domain settings.
 *
 * @SiteimproveDomain(
 *   id = "siteimprovedomain_domain_access",
 *   label = @Translation("Domain access support"),
 *   description = @Translation("Don't set anything and use Drupal's default settings"),
 * )
 */
class DomainAccess extends SiteimproveDomainBase implements SiteimproveDomainInterface {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface &$form_state, $plugin_definition) {
    parent::buildForm($form, $form_state, $plugin_definition);

    $form[$plugin_definition['id']]['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t("This plugin doesn't contain any settings. Domain names are handled automatically."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getUrls(EntityBase $entity) {
    $domain = \Drupal::service('domain_access.manager');
    $urls = $domain->getContentUrls($entity);

    return $urls;
  }

}
