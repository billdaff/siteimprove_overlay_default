<?php

namespace Drupal\siteimprove\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\siteimprove\Plugin\SiteimproveDomainManager;
use Drupal\siteimprove\SiteimproveUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Siteimprove settings.
 *
 * @package Drupal\siteimprove\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * SiteimproveUtils var.
   *
   * @var \Drupal\siteimprove\SiteimproveUtils
   */
  protected $siteimprove;

  /**
   * Drupal\siteimprove\Plugin\SiteimproveDomainManager definition.
   *
   * @var \Drupal\siteimprove\Plugin\SiteimproveDomainManager
   */
  protected $pluginManagerSiteimproveDomain;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, SiteimproveUtils $siteimprove, SiteimproveDomainManager $pluginManagerSiteimproveDomain) {
    parent::__construct($config_factory);

    $this->siteimprove = $siteimprove;
    $this->pluginManagerSiteimproveDomain = $pluginManagerSiteimproveDomain;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('siteimprove.utils'),
      $container->get('plugin.manager.siteimprove_domain')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'siteimprove.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'siteimprove_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('siteimprove.settings');

    $form['container'] = [
      '#title' => $this->t('Token'),
      '#type' => 'fieldset',
    ];

    $form['container']['token'] = [
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Configure Siteimprove Plugin token.'),
      '#maxlength' => 50,
      '#prefix' => '<div id="token-wrapper">',
      '#required' => TRUE,
      '#size' => 50,
      '#suffix' => '</div>',
      '#title' => $this->t('Token'),
      '#type' => 'textfield',
    ];

    $form['container']['request_new_token'] = [
      '#ajax' => [
        'callback' => '::requestToken',
        'wrapper' => 'token-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#type' => 'button',
      '#value' => $this->t('Request new token'),
    ];

    $plugins = $this->pluginManagerSiteimproveDomain->getDefinitions();
    $plugin_definitions = [];
    $options = [];
    foreach ($plugins as $plugin) {
      $options[$plugin['id']] = $plugin['label'];
      $plugin_definitions[$plugin['id']] = $plugin;
    }

    $form['domain'] = [
      '#title' => $this->t('Frontend domain'),
      '#type' => 'fieldset',
    ];

    $form['domain']['domain_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Siteimprove Domain Plugins'),
      '#description' => $this->t('Choose which Siteimprove Domain plugin to use'),
      '#options' => $options,
      '#size' => 1,
      '#default_value' => $config->get('domain_plugin_id'),
      '#weight' => '0',
    ];

    foreach ($options as $key => $option) {
      /** @var \Drupal\siteimprove\Plugin\SiteimproveDomainBase $plugin */
      $plugin_definition = $plugin_definitions[$key];
      $plugin = $this->pluginManagerSiteimproveDomain->createInstance($plugin_definition['id']);
      $plugin->buildForm($form, $form_state, $plugin_definition);
      $form[$plugin_definition['id']]['#states']['visible'] = [
        ':input[name="domain_plugin"]' => [
          'value' => $plugin_definition['id'],
        ],
      ];

      $form['domain'][$plugin_definition['id']] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $plugin_definition['label'] . '</strong><br />' . $plugin_definition['description'],
        '#prefix' => '<div name="' . $plugin_definition['id'] . '_description' . '">',
        '#suffix' => '</div>',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements callback for Ajax event on token request.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Token field with value filled.
   */
  public function requestToken(array &$form, FormStateInterface &$form_state) {

    // Request new token.
    if ($token = $this->siteimprove->requestToken()) {
      $form['container']['token']['#value'] = $token;
    }
    else {
      \Drupal::messenger()->addError($this->t('There was an error requesting a new token. Please try again in a few minutes.'));
    }

    $form_state->setRebuild(TRUE);
    return $form['container']['token'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $domain_plugin = $form_state->getValue('domain_plugin');
    $plugin = $this->pluginManagerSiteimproveDomain->createInstance($domain_plugin);
    $plugin->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $domain_plugin = $form_state->getValue('domain_plugin');
    $this->config('siteimprove.settings')
      ->set('token', $form_state->getValue('token'))
      ->set('domain_plugin_id', $domain_plugin)
      ->save();

    $plugin = $this->pluginManagerSiteimproveDomain->createInstance($domain_plugin);
    $plugin->submitForm($form, $form_state);

  }

}
