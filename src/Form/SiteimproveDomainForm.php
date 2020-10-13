<?php

namespace Drupal\siteimprove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for setting up which Siteimprove Domain plugin to use.
 */
class SiteimproveDomainForm extends FormBase {

  /**
   * Drupal\siteimprove\Plugin\SiteimproveDomainManager definition.
   *
   * @var \Drupal\siteimprove\Plugin\SiteimproveDomainManager
   */
  protected $pluginManagerSiteimproveDomain;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManagerSiteimproveDomain = $container->get('plugin.manager.siteimprove_domain');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siteimprove_domain_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plugins = $this->pluginManagerSiteimproveDomain->getDefinitions();
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = $plugin['label'];
    }
    $form['siteimprove_domain_plugins'] = [
      '#type' => 'select',
      '#title' => $this->t('Siteimprove Domain Plugins'),
      '#description' => $this->t('Choose which Siteimprove Domain plugin to use'),
      '#options' => $options,
      '#size' => 1,
      '#default_value' => '1',
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
