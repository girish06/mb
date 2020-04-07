<?php

namespace Drupal\test_restapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "our_apis_rest",
 *   label = @Translation("get rest resource for our-apis page"),
 *   uri_paths = {
 *     "canonical" = "/our-apis-rest"
 *   }
 * )
 */
class OurApisRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('example_rest'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Gets node title, description and image and store in an array.
    $node = Node::load(20);
    if (!empty($node)) {
      $node_title = $node->getTitle();
      if (!empty($node_title)) {
        $our_api_content['title'] = $node_title;
      }
      $node_body = $node->get('body')->getValue();
      if (!empty($node_body)) {
        $our_api_content['body'] = $node_body;
      }
      $node_image = $node->get('field_image')->entity->uri->value;
      if (!empty($node_image)) {
        $image = file_create_url($node_image);
        $our_api_content['image'] = $image;
      }
    }

    // Gets api product block header title and sub-title.
    $block = \Drupal\block_content\Entity\BlockContent::load(12);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $product_block_header['title'] = $block_title;
      }
      $block_sub_title = $block->field_block_sub_title->value;
      if (!empty($block_sub_title)) {
        $product_block_header['sub_title'] = $block_sub_title;
      }
    }

    // Gets list of api products with title, body and image.
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', 'api_products');
    $and = $query->andConditionGroup();
    $and->condition('field_section', 2);
    $query->condition($and);
    $api_product_nids = $query->execute();
    $product_content = array();
    $key = 0; 
    foreach ($api_product_nids as $value) {
      $node = Node::load($value);
      if (!empty($node)) {
        $product_content[$key]['node_id'] = $value;
        $node_title = $node->getTitle();
        if (!empty($node_title)) {
          $product_content[$key]['title'] = $node_title;
        }
        $node_body = $node->get('body')->getValue();
        if (!empty($node_body)) {
          $product_content[$key]['body'] = $node_body;
        }
        $node_image = $node->get('field_api_product_icon')->entity->uri->value;
        if (!empty($node_image)) {
          $image = file_create_url($node_image);
          $product_content[$key]['image'] = $image;
        }
        $node_swagger_url = $node->get('field_swagger_file_upload')->entity->uri->value;
        if (!empty($node_swagger_url)) {
          $swagger_url = file_create_url($node_swagger_url);
          $product_content[$key]['swagger_url'] = $swagger_url;
        }
      }
      $key++;
    }

    // Gets sign up block header title and sub-title.
    $block = \Drupal\block_content\Entity\BlockContent::load(10);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $signup_block['title'] = $block_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $signup_block['body'] = $block_body;
      }
      $block_image = $block->get('field_basic_block_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $signup_block['image'] = $image;
      }
    }

    // Gets footer banner block title, body and image.
    $block = \Drupal\block_content\Entity\BlockContent::load(11);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $footer_banner['title'] = $block_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $footer_banner['body'] = $block_body;
      }
      $block_image = $block->get('field_banner_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $footer_banner['image'] = $image;
      }
    
    }

    $output = array(
      'top_banner' => $our_api_content,
      'product_block_header' => $product_block_header,
      'product_list' => $product_content,
      'signup_block' => $signup_block,
      'footer_banner' => $footer_banner,
    );
   
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }

}