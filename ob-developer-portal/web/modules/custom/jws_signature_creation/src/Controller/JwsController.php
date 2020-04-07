<?php

namespace Drupal\jws_signature_creation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
* An example controller.
*/
class JwsController extends ControllerBase {

/**
* Returns a render-able array for a test page.
*/
  public function index( Request $request ){

    // Here I can use $request->get('my_textfield') if needed

    $form_state = new FormState();
    $form_state->setRebuild(); // So that the form is rebuilt with previously submitted values
    $form_state->setMethod('GET'); // So that the form is submitted via GET method (URL)

    return \Drupal::formBuilder()->buildForm( '\Drupal\my_module\Form\MyForm', $form_state );
  }

}