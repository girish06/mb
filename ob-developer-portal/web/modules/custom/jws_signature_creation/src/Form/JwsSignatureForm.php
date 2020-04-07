<?php
/**
 * @file
 * Contains Drupal\jws_signature_creation\Form\JwsSignatureForm.
 */
namespace Drupal\jws_signature_creation\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;

class JwsSignatureForm extends FormBase{
public $jwt = '';
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'jsw_signature';
  }
  public function buildForm(array $form, FormStateInterface $form_state)
  {

    $form['payload'] = array(
      '#type' => 'textarea',
      '#prefix' => "<div id='jws-popup-fields'>",
      '#title' => t('payload:'),
      '#required' => TRUE,
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      // Function to call when the users click on submit button.
//      '#ajax' => [
//        'callback' => '::submitForm',
////        'event' => 'click',
//      ],
      '#value' => $this->t('Generate'),
      '#suffix' => "</div>",
    );
    $form['#attached']['library'][] = 'jws_signature_creation/jws-get-key';

//    $form['actions'] = [
//      '#type' => 'button',
//      '#value' => $this->t('Submit'),
//      '#ajax' => [
//        'callback' => '::setMessage',
//      ],
//    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
   // $response = new AjaxResponse();
    $payload  = $form_state->getValue('payload');
    $payload_json = json_decode($payload,TRUE);
    //drupal_set_message($payload_json);
    $payload = json_encode($payload_json);
    $header = [
        "alg"     => "HS256",
        "typ"     => "JWT"
    ];
    $headerEncoded = json_encode($header);
    $headerEncoded = base64_encode($headerEncoded);
    $payloadEncoded = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');

    // Delimit with period (.)
    //$dataEncoded = "$headerEncoded.$payloadEncoded";
    $dataEncoded = "$headerEncoded.";


    $privateKeyResource = "PrivateKey";

    $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, "nZIRzqNNMdf9IpzWrK4lKpQr", TRUE);
    $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    $jwt = "$dataEncoded.$signatureEncoded";
    \Drupal::configFactory()->getEditable('node.settings')
      ->set('items_per_page',$jwt)
      ->save();

    drupal_set_message($jwt. '  in jwt');

    //$form['#attached']['drupalSettings']['mymoduleComputedData'] = $jwt;

    //return $jwt;
   //drupal_set_message($this->t('@client_id_tpp ,Your application is being submitted!', array('@client_id_tpp' => $jwt)));
//    $response->addCommand(
//      new HtmlCommand(
//        '.result_message',
//        '<div class="my_top_message">The result is ' . t('The results is ') . $jwt. '</div>')
//    );
   // $response['#attached']['drupalSettings']['jwt'] = $jwt;
    //$response = $jwt;
    //$response['#attached']['drupalSettings']['jwt'] = $jwt;
   // return $response;
   // print  "testing ".$jwt;

  }


}