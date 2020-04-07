<?php

namespace Drupal\test_restapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\block_content\Entity\BlockContent;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "api_product_detail",
 *   label = @Translation("Get the complete details of the API Product"),
 *   uri_paths = {
 *     "canonical" = "/api-product/{id}"
 *   }
 * )
 */
class ApiProductDetailResource extends ResourceBase {

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
  public function get($id) {
    $output = array();
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    //$nodeId = $id;//$this->currentRequest->get('id');
    $node = Node::load($id);
    if(!empty($node)){
      $top_banner = array();
      $node_title = $node->getTitle();
      if (!empty($node_title)) {
        $top_banner['title'] = $node_title;
      }
      $node_body = $node->get('field_banner_text')->getValue();
      if (!empty($node_body)) {
        $top_banner['body'] = $node_body;
      }
      $node_image = $node->get('field_image')->entity->uri->value;
      if (!empty($node_image)) {
        $image = file_create_url($node_image);
        $top_banner['image'] = $image;
      }
      $node_documentation = $node->get('field_api_documentation_content')->getValue();
      $paragraph_content = Paragraph::load($node_documentation[0]['target_id']);
      $paragraph_title = $paragraph_content->field_documentation_title->getValue();
      $paragraph_overview = $paragraph_content->field_documentation_overview->getValue();
      $paragraph_full_content = $paragraph_content->field_documentation_full_content->getValue();
      $documentation_content = array(
        'paragraph_title' => $paragraph_title,
        'paragraph_overview' => $paragraph_overview,
        'paragraph_full_content' => $paragraph_full_content,
      );
      
      $block = BlockContent::load(16);
        if ($block) {
          $block_title = $block->field_block_title->value;
          if (!empty($block_title)) {
            $footer_banner['title'] = $block_title;
          }
          $block_body = $block->get('body')->value;
          if (!empty($block_body)) {
            $footer_banner['body'] = $block_body;
          }
          $block_image = $block->get('field_basic_block_image')->entity->uri->value;
          if (!empty($block_image)) {
            $image = file_create_url($block_image);
            $footer_banner['image'] = $image;
          }    
        }
      $swagger_url = file_create_url($node->get('field_swagger_file_upload')->entity->uri->value);
      $output = array(
        'top_banner' => $top_banner,
        'product_swagger_url' => $swagger_url,
        'product_documentation_content' => $documentation_content,
        'footer_banner' => $footer_banner
      );
    }
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }
}