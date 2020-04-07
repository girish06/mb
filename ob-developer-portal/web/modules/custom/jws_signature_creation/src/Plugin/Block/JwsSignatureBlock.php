<?php

namespace Drupal\jws_signature_creation\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'JwsSignature' block.
 *
 * @Block(
 *  id = "jws_signature_creation",
 *  admin_label = @Translation("Jws Signature block"),
 * )
 */
class JwsSignatureBlock extends BlockBase {
  public function build() {


      $form = \Drupal::formBuilder()->getForm('Drupal\jws_signature_creation\Form\JwsSignatureForm');

      return $form;
    }


}
