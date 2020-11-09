<?php

namespace Drupal\siteimprove;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Utility functions for Siteimprove.
 */
class SiteimproveUtils {

  use StringTranslationTrait;

  const TOKEN_REQUEST_URL = 'https://my2.siteimprove.com/auth/token?cms=Drupal-' . \Drupal::VERSION;

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
  public function getSiteimproveSettings(array $url, $type, $auto = TRUE) {
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
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
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
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Entity to get frontend urls for.
   *
   * @return array|\Drupal\Core\GeneratedUrl|string
   *   Returns an array of frontend urls for entity.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getEntityUrls($entity) {
    if (!$entity->hasLinkTemplate('canonical')) {
      return [];
    }

    $domains = $this->getEntityDomains($entity);

    /** @var \Drupal\Core\Entity\Entity $entity */
    $url_relative = $entity->toUrl('canonical', ['absolute' => FALSE])->toString();
    $urls = [];

    // Create urls for active frontend urls for the entity.
    foreach ($domains as $domain) {
      $urls[] = $domain . $url_relative;
    }

    $frontpage = $this->configFactory->get('system.site')->get('page.front');
    $current_route_name = \Drupal::routeMatch()->getRouteName();
    $node_route = in_array($current_route_name, [
      'entity.node.edit_form',
      'entity.node.latest_version',
    ]);
    $taxonomy_route = in_array($current_route_name, [
      'entity.taxonomy_term.edit_form',
      'entity.taxonomy_term.latest_version',
    ]);

    // If entity is frontpage, add base url to domains.
    if (\Drupal::service('path.matcher')->isFrontPage()
      || ($node_route && '/node/' . $entity->id() === $frontpage)
      || ($taxonomy_route && '/taxonomy/term/' . $entity->id() === $frontpage)
    ) {
      $front = Url::fromRoute('<front>')->toString();
      foreach ($domains as $domain) {
        $urls[] = $domain . $front;
      }
    }

    return $urls;

  }

  /**
   * Get active domain names for entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Entity to get domain names for.
   *
   * @return array
   *   Array of domain names without trailing slash.
   */
  public function getEntityDomains($entity) {
    // Get the active frontend domain plugin.
    $config = \Drupal::service('config.factory')->get('siteimprove.settings');
    $plugin_manager = \Drupal::getContainer()->get('plugin.manager.siteimprove_domain');
    $plugin_id = $config->get('domain_plugin_id');
    /** @var \Drupal\siteimprove\Plugin\SiteimproveDomainBase $plugin */
    $plugin = $plugin_manager->createInstance($plugin_id);

    // Get active domains.
    return $plugin->getUrls($entity);
  }

}
