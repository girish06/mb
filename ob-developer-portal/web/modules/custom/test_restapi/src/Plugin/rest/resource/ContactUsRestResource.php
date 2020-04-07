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
 *   id = "contact_us_rest",
 *   label = @Translation("Get rest resource for Contact Us Page"),
 *   uri_paths = {
 *     "canonical" = "/contact-us-rest"
 *   }
 * )
 */
class ContactUsRestResource extends ResourceBase {

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
    $node = Node::load(21);
    if (!empty($node)) {
      $node_title = $node->getTitle();
      if (!empty($node_title)) {
        $contact_us_banner['title'] = $node_title;
      }
      $node_body = $node->get('body')->getValue();
      if (!empty($node_body)) {
        $contact_us_banner['body'] = $node_body;
      }
      $node_image = $node->get('field_image')->entity->uri->value;
      if (!empty($node_image)) {
        $image = file_create_url($node_image);
        $contact_us_banner['image'] = $image;
      }
    }

    // Gets contact us form header block title and sub-title.
    $block = \Drupal\block_content\Entity\BlockContent::load(13);
    if ($block) {
      $block_title = $block->field_block_title->value;
      if (!empty($block_title)) {
        $contact_us_block_header['title'] = $block_title;
      }
      $block_sub_title = $block->field_block_sub_title->value;
      if (!empty($block_sub_title)) {
        $contact_us_block_header['sub_title'] = $block_sub_title;
      }
    }

    // Gets webforms title and description.
    $query = \Drupal::service('entity.query')->get('webform');
    $query->condition('category', 'Contact Us');
    $entity_ids = $query->execute();
    
    $webform_id=array();
    foreach($entity_ids as $webid){
      $webform_id[] = $webid;     
    }

    $webform_data = array();
    $key = 0;
    $webform = Webform::loadMultiple($webform_id);
    foreach($webform as $webforms){
      $webform_data[$key]['webform_id'] = $webforms->get('id');
      $webform_data[$key]['webform_title'] = $webforms->get('title');
      $webform_data[$key]['webform_description'] = $webforms->get('description');
      $link_title = str_replace(' ', '-', strtolower($webform_data[$key]['webform_title']));
      $webform_data[$key]['webform_link'] = $link_title;
      $key++;
    }

    // Gets footer banner block title, body and image.
    $block = \Drupal\block_content\Entity\BlockContent::load(14);
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
      'top_banner' => $contact_us_banner,
      'contact_us_block_header' => $contact_us_block_header,
      'webform_data' => $webform_data,
      'footer_banner' => $footer_banner,
    );
   
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }

}