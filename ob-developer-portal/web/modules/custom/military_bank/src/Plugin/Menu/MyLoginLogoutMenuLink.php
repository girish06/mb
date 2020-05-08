<?php

namespace Drupal\military_bank\Plugin\Menu;

use Drupal\user\Plugin\Menu\LoginLogoutMenuLink;

class MyLoginLogoutMenuLink extends LoginLogoutMenuLink {

  public function getTitle() {
    //    if ($this->currentUser->isAuthenticated()) {
    if ($this->currentUser->isAnonymous()) {
      return $this->t('Đăng nhập');
    }
    else {
      return $this->t('Thoát ra');
    }
  }

}
