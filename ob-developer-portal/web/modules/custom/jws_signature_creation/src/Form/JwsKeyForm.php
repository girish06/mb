<?php
/**
 * @file
 * Contains Drupal\jws_signature_creation\Form\JwsKeyForm.
 */
namespace Drupal\jws_signature_creation\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class JwsKeyForm extends FormBase{

  public $jwt = '';
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jsw_signature_key';
  }
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    //$jwtkey  = $form_state->getValue('jwt_secret_key');
    $jwtkey = \Drupal::config('node.settings')->get('key_page');
    if(empty($jwtkey)){
    $jwtkey ='';
    }

    $form['jwt_secret_key'] = array(
      '#type' => 'textfield',
//      '#prefix' => "<div id='jws-popup-fields'>",
      '#title' => t('Enter secret key for JWS:'),
      '#required' => TRUE,
      '#default_value' => $jwtkey,
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      // Function to call when the users click on submit button.
//      '#ajax' => [
//        'callback' => '::submitForm',
////        'event' => 'click',
//      ],
      '#value' => $this->t('Save'),
//      '#suffix' => "</div>",
    );
    $form['#attached']['library'][] = 'jws_signature_creation/jws-get-key';
    return $form;

  }
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $jwtkey_op  = $form_state->getValue('jwt_secret_key');
    \Drupal::configFactory()->getEditable('node.settings')
      ->set('key_page',$jwtkey_op)
      ->save();
    $build['#attached']['drupalSettings']['jwssign']['jwskey'] = $jwtkey_op;
    return $build;
    // TODO: Implement submitForm() method.
  }
}