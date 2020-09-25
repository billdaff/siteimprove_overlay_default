<?php

namespace Drupal\siteimprove;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SiteimproveUtils.
 */
class SiteimproveUtils {

  use StringTranslationTrait;

  const TOKEN_REQUEST_URL = 'https://my2.siteimprove.com/auth/token?cms=nameAndVersionofCMSPlugin';

  /**
   * Current user var.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * ConfigFactory var.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user, Client $http_client) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('http_client')
    );
  }

  /**
   * Return Siteimprove token.
   */
  public function requestToken() {

    try {
      // Request new token.
      $response = $this->httpClient->get(self::TOKEN_REQUEST_URL,
        ['headers' => ['Accept' => 'application/json']]);

      $data = (string) $response->getBody();
      if (!empty($data)) {
        $json = json_decode($data);
        if (!empty($json->token)) {
          return $json->token;
        }
        else {
          throw new \Exception();
        }
      }
      else {
        throw new \Exception();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('siteimprove', $e, $this->t('There was an error requesting a new token.'));
    }

    return FALSE;
  }

  /**
   * Return Siteimprove js library.
   *
   * @return string
   *   Siteimprove js library.
   */
  public function getSiteimproveOverlayLibrary() {
    return 'siteimprove/siteimprove.overlay';
  }

  /**
   * Return siteimprove js library.
   */
  public function getSiteimproveLibrary() {
    return 'siteimprove/siteimprove';
  }

  /**
   * Return siteimprove js settings.
   *
   * @param array $url
   *   Urls to input or recheck.
   * @param string $type
   *   Action: recheck_url|input_url.
   * @param bool $auto
   *   Automatic calling to the defined method.
   *
   * @return array
   *   JS settings.
   */
  public function getSiteimproveSettings($url, $type, $auto = TRUE) {
    return [
      'url' => $url,
      'auto' => $auto,
    ];
  }

  /**
   * Return siteimprove token.
   *
   * @return array|mixed|null
   *   Siteimprove Token.
   */
  public function getSiteimproveToken() {
    return $this->configFactory->get('siteimprove.settings')->get('token');
  }

  /**
   * Save URL in session.
   *
   * @param object $entity
   *   Node or taxonomy term entity object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function setSessionUrl($entity) {
    // Check if user has access.
    if ($this->currentUser->hasPermission('use siteimprove')) {
      $urls = $this->getEntityUrls($entity);

      // Save friendly url in SESSION.
      foreach ($urls as $url) {
        $_SESSION['siteimprove_url'][] = $url;
      }
    }
  }

  /**
   * Return frontend urls for given entity.
   *
   * @param $entity
   *
   * @return array|\Drupal\Core\GeneratedUrl|string
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getEntityUrls($entity) {
    if (!$entity->hasLinkTemplate('canonical')) {
      return [];
    }
    /** @var \Drupal\Core\Entity\Entity $entity */
    $url_relative = $entity->toUrl('canonical', ['absolute' => FALSE])->toString();
    $urls = [];

    // Get the active frontend domain plugin.
    $config = \Drupal::service('config.factory')->get('siteimprove.settings');
    $plugin_manager = \Drupal::getContainer()->get('plugin.manager.siteimprove_domain');
    $plugin_id = $config->get('domain_plugin_id');
    /** @var \Drupal\siteimprove\Plugin\SiteimproveDomainBase $plugin */
    $plugin = $plugin_manager->createInstance($plugin_id);

    // Get active domains.
    $domains = $plugin->getUrls($entity);

    // Create urls for active frontend urls for the entity.
    foreach ($domains as $domain) {
      $urls[] = $domain . $url_relative;
    }

    if (empty($urls)) {
      return [$entity->toUrl('canonical', ['absolute' => TRUE])->toString()];
    }
    else {
      return $urls;
    }

  }

}
