<?php

namespace Drupal\military_bank\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Legacy mail Parameter Selector.
 *
 * @ViewsArgumentDefault(
 *   id = "mbroleview",
 *   title = @Translation("Mb Role")
 * )
 */
class MbRoleView extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    $roles = \Drupal::currentUser()->getRoles();
    $roles_formatted = implode("+",$roles );
    return $roles_formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
