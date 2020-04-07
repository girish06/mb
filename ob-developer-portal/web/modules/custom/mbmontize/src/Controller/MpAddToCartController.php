<?php

namespace Drupal\mbmontize\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\commerce\commerce_product;
use Drupal\commerce;
use Drupal\commerce_cart;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Class MpAddToCartController.
 */
class MpAddToCartController extends ControllerBase {

    /**
     * The cart manager.
     *
     * @var \Drupal\commerce_cart\CartManagerInterface
     */
    protected $cartManager;

    /**
     * The cart provider.
     *
     * @var \Drupal\commerce_cart\CartProviderInterface
     */
    protected $cartProvider;

    /**
     * Constructs a new CartController object.
     *
     * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
     *   The cart provider.
     */
    public function __construct(CartManagerInterface $cart_manager,CartProviderInterface $cart_provider) {
        $this->cartManager = $cart_manager;
        $this->cartProvider = $cart_provider;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('commerce_cart.cart_manager'),
            $container->get('commerce_cart.cart_provider')
        );
    }

   /**
   * Cart.
   *
   * @return string
   *   Return Hello string.
   */
  public function addCart(){
      if (\Drupal::currentUser()->isAnonymous()) {
          $responce =  new RedirectResponse('/user/login');
          return $responce->send();
      }
      $query = \Drupal::request()->query;
      $item_id = $query->get('item_id');
      $vid = $query->get('vid');

      //$destination = \Drupal::service('path.current')->getPath();
      $productObj = Product::load($item_id);

      //$product_variation_id = $productObj->get('variations')->getValue()[0]['target_id'];
      $storeId = $productObj->get('stores')->getValue()[0]['target_id'];
      $variationobj = \Drupal::entityTypeManager()
          ->getStorage('commerce_product_variation')
          ->load($vid);
      $store = \Drupal::entityTypeManager()
          ->getStorage('commerce_store')
          ->load($storeId);
      $cart = $this->cartProvider->getCart('default', $store);
      if (!$cart) {
          $cart = $this->cartProvider->createCart('default', $store);
      }
      // $line_item_type_storage = \Drupal::entityTypeManager()->getStorage('commerce_order_item_type');
      // Process to place order programatically.
      $cart_manager = \Drupal::service('commerce_cart.cart_manager');
      $cart_manager->addEntity($cart, $variationobj);
      $responce =  new RedirectResponse('/cart');
      return $responce->send();
  }
}
