<?php

namespace Drupal\siteimprove;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormAlter.
 */
class EntityFormAlter {

  /**
   * Alter node/taxonomy term edit form.
   */
  public static function siteimprove(array $element, FormStateInterface $form_state) {
    // Get friendly url of node and include all Siteimprove js scripts.
    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = $form_state->getFormObject()->getEntity();

    if (!$entity->hasLinkTemplate('canonical')) {
      return $element;
    }

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

    // If plugin doesn't return any domain, use the default.
    if (empty($domains)) {
      $urls[] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    }

    $element['#attached']['drupalSettings']['siteimprove']['input'] = \Drupal::service('siteimprove.utils')->getSiteimproveSettings($urls, 'input');
    $element['#attached']['drupalSettings']['siteimprove']['recheck'] = \Drupal::service('siteimprove.utils')->getSiteimproveSettings($urls, 'recheck', FALSE);

    return $element;
  }

}
