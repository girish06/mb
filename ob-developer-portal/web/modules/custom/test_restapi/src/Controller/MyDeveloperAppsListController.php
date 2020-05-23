<?php

namespace Drupal\test_restapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * Class MyDeveloperAppsListController.
 */
class MyDeveloperAppsListController extends ControllerBase {

  /**
   * Getlistmyapps.
   *
   * @return string
   *   Return Hello string.
   */
  public function getListMyApps() {
      $userId = \Drupal::currentUser()->id();
      $userDetails = User::load($userId);
      $developerId = $userDetails->get('apigee_edge_developer_id')->value;
      $storageDeveloperApp = \Drupal::entityTypeManager()->getStorage('developer_app');
      $query = $storageDeveloperApp->getQuery()->condition('developerId',$developerId);
      $entityIds = $query->execute();
      $developerApps = $storageDeveloperApp->loadMultiple($entityIds);
    return [
      '#theme' => 'open_banking_my_apps',
      '#my_apps' => $developerApps,
      '#user_id' => $userId,
    ];
  }

}
