<?php

namespace ShipperHQ\WS\Client;

/**
 * Class WebServiceClient
 *
 * @package ShipperHQ\WS\Client
 */
class WebServiceClient {

   private $shipperHQWebServiceHost    =  "http://www.localhost.com:8080/shipperhq-ws";
   private $shipperHQWebServiceVersion =  1;

   /**
    * @param \ShipperHQ\WS\Request\WebServiceRequest $requestObj
    * @param $webServicePath
    * @return mixed|null
    */
   public function sendAndReceive(\ShipperHQ\WS\Request\WebServiceRequest $requestObj, $webServicePath) {

      $webServiceURL = $this->shipperHQWebServiceHost . "/v" . $this->shipperHQWebServiceVersion .
                       "/" . $webServicePath;

      try {

         $client = new \Zend_Http_Client();
         $client->setUri($webServiceURL);
         $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
         $client->setRawData(json_encode($requestObj), 'application/json');
         $response = $client->request(\Zend_Http_Client::POST);
         $responseBody = $response->getBody();

         return json_decode($responseBody, false);
      }
      catch (\Exception $e) {
         $debugData['error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
      }
      return null;
   }
}