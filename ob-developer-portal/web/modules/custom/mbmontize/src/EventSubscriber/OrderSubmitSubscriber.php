<?php

namespace Drupal\mbmontize\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Apigee\Edge\Api\Management\Controller\DeveloperAppCredentialController;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OrderSubmitSubscriber.
 *
 * @package Drupal\market_place
 */
class OrderSubmitSubscriber implements EventSubscriberInterface {

    public const LEGAL_NAME = 'MINT_DEVELOPER_LEGAL_NAME';

    public const LEGAL_PURCHASED_PLANS = 'apigee_my_purchased_plans';

  /**
   * Constructor.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['submitOrderToVendor'];
    return $events;
  }

  /**
   *  Called on event commerce_order.place.post_transition is dispatched.
   *
   * @param WorkflowTransitionEvent $event
   *   Order workflow transition event object.
   */
  public function submitOrderToVendor(WorkflowTransitionEvent $event) {
      
      // To get value of product bundle id and rate plan
      // To do : needs validation if multiple order items persists in an order.
      $order = $event->getEntity();
      $order_item_id = $order->get('order_items')->getValue()[0]['target_id'];
      $order_item_data = OrderItem::load($order_item_id);
      $purchased_entity = $order_item_data->getPurchasedEntity();
      $order_id = $order_item_data->getOrderId();

      $orderObj = Order::load($order_id);
      $order_total_price = $orderObj->getTotalPrice()->getNumber();
      $balance = intval($order_total_price);

      $product = $purchased_entity->getProduct();

      // Check condition for MONITIZATION product type.
      if ($product->bundle() == 'default') {
          $rate_plan= $purchased_entity->get('field_rate_plan_apigee')->getValue()[0]['target_id'];
          $package_plan= $purchased_entity->get('field_mint_package')->getValue()[0]['target_id'];
          $commerce_pid = $purchased_entity->get('product_id')->getValue()[0]['target_id'];
          // To update developer balance in apigee end.
          $userId = \Drupal::currentUser()->id();
          $user = \Drupal\user\Entity\User::load($userId);
          $developerId = $user->get('apigee_edge_developer_id')->value;


          set_apigee_developer_balance($developerId,$balance);
          sleep(1);


          //This should be Apigee Developer Entity;
          $developer = \Drupal\apigee_edge\Entity\Developer::load($developerId);
          $rate = \Drupal\apigee_m10n\Entity\RatePlan::loadById($package_plan,$rate_plan);

          /* This should be Moentisation Developer Entity only not Apigee Developer Entity */
          $purchased_plan = \Drupal\apigee_m10n\Entity\PurchasedPlan::create([
              'ratePlan' => $rate,
              'developer' => new \Apigee\Edge\Api\Monetization\Entity\Developer(['email' => $user->getEmail()]),
              'startDate' => new \DateTimeImmutable(),
            ]);
            if (empty($developer->getAttributeValue(static::LEGAL_NAME))) {
                $developer->setAttribute(static::LEGAL_NAME, $developerId);
                $developer->save();
            }

          // Creating a app.
          $entity = \Drupal::entityTypeManager()->getStorage('developer_app')->create();
          $entity->setOwnerId($userId);
          $entity->set('displayName',$order_item_id.'-'.'TestCreation');
          $entity->set('name',$order_item_id.'-'.'TestCreation'.rand(11111,999999));
          $entity->set('description','Test');
          $entity->set('callbackUrl','');
          $entity->save();
          $appId = $entity->id();
          $config = \Drupal::configFactory()->get('apigee_edge.common_app_settings');
          $apiProducts = \Drupal\apigee_m10n\Entity\ProductBundle::load($package_plan)->getMonetizationApiProducts();
          $products = [];
          foreach($apiProducts as $value) {
              $products[] = $value->getName();
          }
          $products = array_values(array_filter($products));
          $dacc = new DeveloperAppCredentialController(\Drupal::service('apigee_edge.sdk_connector')->getOrganization(), $entity->getDeveloperId(), $entity->getName(), \Drupal::service('apigee_edge.sdk_connector')->getClient());
          /** @var \Apigee\Edge\Api\Management\Entity\AppCredential[] $credentials */
          $credentials = $entity->getCredentials();
          /** @var \Apigee\Edge\Api\Management\Entity\AppCredential $credential */
          $credential = reset($credentials);
          $credential_lifetime = \Drupal::configFactory()->get('apigee_edge.developer_app_settings')->get('credential_lifetime');
          if ($credential_lifetime === 0) {
              $dacc->addProducts($credential->id(), $products);
          }
          else {
              $dacc->delete($credential->id());
              // The value of -1 indicates no set expiry. But the value of 0 is not
              // acceptable by the server (InvalidValueForExpiresIn).
              $dacc->generate($products, $entity->getAttributes(), $entity->getCallbackUrl(), [], $credential_lifetime * 86400000);
          }
          $time = time();
          // service call for insert produtc details.
          //\Drupal::service('product_access')->insertProductDetails($order_item_id,$commerce_pid,$userId,$appId,$order_id,$package_plan,$time);
          //insertProductDetails($order_item_id,$commerce_pid,$userId,$appId,$order_id,$package_plan,$time);
          $purchased_plan->save();
          \Drupal\Core\Cache\Cache::invalidateTags([static::LEGAL_PURCHASED_PLANS]);
      }
    \Drupal::messenger()->addMessage('Your Order is successfully placed. Please check your subscriptions');
    //$response = new RedirectResponse(URL::fromUserInput('/my-dashboard')->toString());
    //$response->send();
  }
}


function insertProductDetails($order_item_id,$commerce_pid,$userId,$app_id,$order_id,$package_plan, $time) {
    // Insert query for product details for monitization.
    \Drupal::database()->insert('product_details')
        ->fields([
            'product_id' => $order_item_id,
            'commerce_pid' => $commerce_pid,
            'user_id' => $userId,
            'app_id' => $app_id,
            'order_id' => $order_id,
            'app_name' => $package_plan,
            'created_time' => $time,
        ])->execute();
}

function set_apigee_developer_balance($developerId, $balance){
    try {
        $developerBalance = new \Apigee\Edge\Api\Monetization\Controller\DeveloperPrepaidBalanceController($developerId,\Drupal::service('apigee_edge.sdk_connector')->getOrganization(),\Drupal::service('apigee_edge.sdk_connector')->getClient());
        $supportedCurrency = new \Apigee\Edge\Api\Monetization\Controller\SupportedCurrencyController(\Drupal::service('apigee_edge.sdk_connector')->getOrganization(),\Drupal::service('apigee_edge.sdk_connector')->getClient());
        $currency = array_keys($supportedCurrency->getEntities());
        $currency = (empty($currency)) ?  'usd' : $currency[0];
        $developerBalance->topUpBalance((float)$balance, $currency);
    }
    catch(\Exception $exception) {
        \Drupal::messenger()->addError('Failed to developer balance.');
        $context = Error::decodeException($exception);
        \Drupal::logger('mbmonitize')->error('Failed to developer balance. @message %function (line %line of %file). <pre>@backtrace_string</pre>', $context);
    }

}


