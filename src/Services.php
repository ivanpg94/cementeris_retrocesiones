<?php

namespace Drupal\cementeris_retrocesiones;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Service description.
 */
class Services {

  /**
   * A logger instance.
   *
   * @var LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The cache.backend.database service.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $cacheBackendDatabase;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The HTTP client.
   *
   * @var ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Constructs a CementterisRetrocesionesServices object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_backend_database
   *   The cache.backend.database service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   */
  public function __construct(LoggerInterface $logger, MessengerInterface $messenger, CacheFactoryInterface $cache_backend_database, AccountInterface $account, ClientInterface $client) {
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->cacheBackendDatabase = $cache_backend_database;
    $this->account = $account;
    $this->httpClient = $client;
  }

  /**
   * Method description.
   */
  public function postCementiris($paramsJson) {

    try {
      $client = new Client();
      $parms = [
        'headers' => ['Content-Type' => 'application/json'],
        'json' => $paramsJson,
      ];
      $response = $client->post('https://accfun.cbsa.cat/gas/ws/r/RetroSolicPRE', $parms);
      return Json::decode($response->getBody()->getContents());
    }
    catch (ConnectException $e) {
      $this->customHandlerExceptionMessage($e);
      $this->send_email('No se pudo conectar al webservice.');
      return [];
    }
    catch (RequestException $e) {
      $this->logger->error('Comunication Error in PostCementiris, ' . $e);
      $this->customHandlerExceptionMessage($e);
      return [];
    }
    catch (GuzzleException $e) {
      $this->logger->error('Error de Guzzle, ' . $e);
      $this->customHandlerExceptionMessage($e);
      return [];
    }
  }

  /**
   * @param $error
   *
   * @return void
   */
  public function send_email($error): void {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'cementeris_retrocesiones';
    $key = 'webservice_error';
    $to = 'soporte@bsm.com';
    $params['subject'] = 'Error de conexión con el WebService';
    $params['message'] = "Ocurrió un error al conectar con el WebService:\n\n$error";
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
  }

  /**
   * Method error message.
   *
   * @param $e
   * @return void
   */
  protected function customHandlerExceptionMessage($e): void
  {
    $this->logger->error($e);
    // Show messages only for administrator role
    if (in_array('administrator', $this->account->getRoles())) {
      if ($e instanceof RequestException && $e->hasResponse()) {
        try {
          $error_content = Json::decode($e->getResponse()->getBody()->getContents());
          $this->messenger->addWarning($error_content);
        } catch (\Exception $jsonException) {
          $this->messenger->addWarning('An error occurred: ' . $e->getMessage());
        }
      } else {
        $this->messenger->addWarning('An error occurred: ' . $e->getMessage());
      }
    }
  }

}


