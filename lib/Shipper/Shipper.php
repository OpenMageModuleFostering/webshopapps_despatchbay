<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
/**
 * ShipperHQ Library Class
 *
 * @category   ShipperHQ
 * @package    Shipperhq_Shipper
 *
 */

class Shipper_Shipper
{

    const LIVE = 'LIVE';
    const DEV  = 'DEV';
    const TEST = 'TEST';
    const INTEGRATION = 'INTEGRATION';

    /**
     * Retrieve allowed methods from all the carriers
     *
     * @param        $request
     * @param string $gatewayUrl Gateway to call
     * @return array List of allowed methods
     */
    public function getAllowedMethods($request, $gatewayUrl)
    {
       return $this->submitRequest($request, $gatewayUrl);

    }

    /**
     * Shipper library entry point
     *
     * @param $request
     * @param $gatewayUrl
     * @return array Contains response and logging of what happened
     */
    public function getRates($request,$gatewayUrl)
    {
        return $this->submitRequest($request, $gatewayUrl);
    }

    /**
     * Retreive latest attribute values
     *
     * @param string $request Formatted request according to ShipperHQ API
     * @param        $gatewayUrl
     * @return array Contains response and logging of what happened
     */
    public function getLatestAttributes($request,$gatewayUrl)
    {
        return $this->submitRequest($request, $gatewayUrl);
    }

    /**
     * Retreive last time attributes were synchronized
     *
     * @param string $request Formatted request according to ShipperHQ API
     * @param        $gatewayUrl
     * @return array Contains response and logging of what happened
     */
    public function checkSynchronized($request,$gatewayUrl)
    {
        return $this->submitRequest($request, $gatewayUrl);
    }

    /**
     * Set Synchronized status
     *
     * @param string $request Formatted request according to ShipperHQ API
     * @param        $gatewayUrl
     * @return array Contains response and logging of what happened
     */
    public function setSynchronized($request,$gatewayUrl)
    {
        return $this->submitRequest($request, $gatewayUrl);
    }

    /**
     * Submit request to ShipperHQ
     *
     * @param $request
     * @param $gatewayUrl
     * @return array Contains response and logging of what happened
     */
    public function submitRequest($request,$gatewayUrl)
    {

        $jsonRequest = json_encode($request);
        $debugData['json_request'] = $jsonRequest;
        $debugData['url'] = $gatewayUrl;
        $response = '';

        try {
            $client = new Zend_Http_Client();
            $client->setUri($gatewayUrl);
            $client->setConfig(array('maxredirects'=>0, 'timeout'=>30));
            $client->setRawData($jsonRequest,'application/json');
            $response = $client->request(Zend_Http_Client::POST);
            $responseBody = $response->getBody();

            $debugData['response'] = $responseBody;
            $responseBody = json_decode($responseBody, false);
        }
        catch (Exception $e) {
            $debugData['error'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            $responseBody = '';
        }

        $result = array('result' => $responseBody, 'debug' => $debugData);

        return $result;
    }
}
