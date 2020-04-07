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
 *   id = "experience_banking_apis",
 *   label = @Translation("Get rest resource for Experience Banking page"),
 *   uri_paths = {
 *     "canonical" = "/experience-banking-apis"
 *   }
 * )
 */
class ExperienceBankingRestResource extends ResourceBase {

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

    // Gets top banner block title, body and image.
    $block = \Drupal\block_content\Entity\BlockContent::load(4);
    if ($block) {
      $block_title = $block->get('field_banner_title')->value;
      if (!empty($block_title)) {
        $top_banner['title'] = $block_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $top_banner['body'] = $block_body;
      }
      $block_image = $block->get('field_banner_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $top_banner['image'] = $image;
      }   
    }

    // Experience Banking API block header title, sub-title and body.
    $block = \Drupal\block_content\Entity\BlockContent::load(6);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $api_block_header['title'] = $block_title;
      }
      $block_sub_title = $block->field_block_sub_title->value;
      if (!empty($block_sub_title)) {
        $api_block_header['sub_title'] = $block_sub_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $api_block_header['body'] = $block_body;
      }
      $block_image = $block->get('field_basic_block_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $api_block_header['image'] = $image;
      } 
    }

    // Gets list of api products with title, body and image.
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', 'api_products');
    $and = $query->andConditionGroup();
    $and->condition('field_section', 1);
    $query->condition($and);
    $api_product_nids = $query->execute();
    $product_content = array();
    $key = 0; 
    foreach ($api_product_nids as $value) {
      $node = Node::load($value);
      $product_content[$key]['id'] = $value;
      if (!empty($node)) {
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
      }
      $key++;
    }

    // Gets delight apps block header title and sub-title.
    $block = \Drupal\block_content\Entity\BlockContent::load(7);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $delight_apps_header['title'] = $block_title;
      }
      $block_body = $block->get('body')->value;
      if (!empty($block_body)) {
        $delight_apps_header['body'] = $block_body;
      }
      $block_image = $block->get('field_basic_block_image')->entity->uri->value;
      if (!empty($block_image)) {
        $image = file_create_url($block_image);
        $delight_apps_header['image'] = $image;
      }
    }

    // Gets api product block header title and sub-title.
    $block = \Drupal\block_content\Entity\BlockContent::load(8);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $apps_video_header['title'] = $block_title;
      }
      $block_sub_title = $block->field_block_sub_title->value;
      if (!empty($block_sub_title)) {
        $apps_video_header['sub_title'] = $block_sub_title;
      }
    }

    // Gets list of delight apps with title, body and image.
    $query = \Drupal::entityQuery('node')
      ->condition('status', NODE_PUBLISHED)
      ->condition('type', 'open_banking_apps');
    $apps_video_nids = $query->execute();
    $apps_video_content = array();
    $key = 0; 
    foreach ($apps_video_nids as $value) {
      $node = Node::load($value);
      $apps_video_content[$key]['id'] = $value;
      if (!empty($node)) {
        $node_title = $node->getTitle();
        if (!empty($node_title)) {
          $apps_video_content[$key]['title'] = $node_title;
        }
        $node_body = $node->get('body')->getValue();
        if (!empty($node_body)) {
          $apps_video_content[$key]['body'] = $node_body;
        }
        $node_video = $node->get('field_app_video')->entity->uri->value;
        if (!empty($node_video)) {
          $video = file_create_url($node_video);
          $apps_video_content[$key]['video'] = $video;
        }
      }
      $key++;
    }

    // Gets footer banner block title, body and image.
    $block = \Drupal\block_content\Entity\BlockContent::load(5);
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
      'top_banner' => $top_banner,
      'api_block_header' => $api_block_header,
      'product_list' => $product_content,
      'delight_apps_header' => $delight_apps_header,
      'apps_video_header' => $apps_video_header,
      'apps_video_content' => $apps_video_content,
      'footer_banner' => $footer_banner,
    );
   
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }

}