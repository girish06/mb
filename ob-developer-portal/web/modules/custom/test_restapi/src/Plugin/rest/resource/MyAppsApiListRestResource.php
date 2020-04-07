<?php

namespace Drupal\test_restapi\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\apigee_edge\SDKConnectorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "my_apps_apis_list",
 *   label = @Translation("Get the List of all the API's"),
 *   uri_paths = {
 *     "canonical" = "/user/api-list"
 *   }
 * )
 */
class MyAppsApiListRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  
  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;
  
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  
  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;
  
  /**
   * The SDK Connector service.
   *
   * @var \Drupal\apigee_edge\SDKConnectorInterface
   */
  protected $sdkConnector;
  
  /**
   * The SDK Connector service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  
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
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\apigee_edge\SDKConnectorInterface $sdk_connector
   *   The SDK Connector service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeInterface $entity_type, 
    EntityStorageInterface $storage, 
    EntityTypeManagerInterface $entity_type_manager,
    SDKConnectorInterface $sdk_connector, 
    ConfigFactoryInterface $config_factory,
    Request $currentRequest) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->currentRequest = $currentRequest;
    $this->entityType = $entity_type;
    $this->storage = $storage;
    $this->sdkConnector = $sdk_connector;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      $entity_type = $container->get('entity_type.manager')->getDefinition('developer_app');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('example_rest'),
      $container->get('current_user'),
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('apigee_edge.sdk_connector'),
      $container->get('config.factory'),
      $container->get('request_stack')->getCurrentRequest()
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
    $output = array();
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    /** 
     * @var \Drupal\apigee_edge\Entity\ApiProductInterface[] $availableProductsForUser 
     */
//    $availableProductsForUser = array_filter(ApiProduct::loadMultiple(), function (ApiProductInterface $product) use ($user) {
//        return $product->access('assign', $user);
//    });
//    $product_list = [];
//    foreach ($availableProductsForUser as $product) {
//        $product_list[$product->id()] = $product->label();
//    }
    $api_list = $this->entityTypeManager->getStorage('api_product')->loadMultiple();
    $output['my_api_list'] = array_keys($api_list);
    $response = new ResourceResponse($output);
    $response->addCacheableDependency($output);
    return $response;
  }
}