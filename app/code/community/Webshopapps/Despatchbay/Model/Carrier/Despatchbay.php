<?php

/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Josh Stewart
 * Date         14 April 2014
 * Time         16:00
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Carrier_Despatchbay
    extends Webshopapps_Wsacommon_Model_Shipping_Carrier_Baseabstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'despatchbay';

    protected $_activeFlag = 'active';

    protected $modName = 'none'; //Want to use inbult Magento logging, not wsalogger

    //ShipperHQ Variables
    protected $_shipperWSInstance = null;
    protected $_rawRatesRequest = null;
    protected $_result = null;

    //Label & Shipment Variables
    const TRACK = 1;
    const SHIPMENT = 2;
    const LABEL = 3;

    const TRACK_URL = 'https://api.despatchbaypro.com/soap/v11/tracking?wsdl';
    const SHIPMENT_URL = 'https://api.despatchbaypro.com/soap/v11/shipping?wsdl';
    const LABEL_URL = 'https://api.despatchbaypro.com/pdf/1.0.1/labels';

    protected $_shipmentServiceWsdl = '';
    protected $_trackServiceWsdl = '';

    protected $_rawTrackingRequest = '';

    protected $_helper = null;

    public function __construct()
    {
        parent::__construct();
        $wsdlBasePath = Mage::getModuleDir('etc', 'Webshopapps_Despatchbay') . DS . 'wsdl' . DS . 'Despatchbay' . DS;

        $this->_shipmentServiceWsdl = $wsdlBasePath . 'Shippingv11.wsdl';
        $this->_trackServiceWsdl = $wsdlBasePath . 'Trackingv11.wsdl';
        $this->_helper = Mage::helper('webshopapps_despatchbay');
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag($this->_activeFlag)) {
            return false;
        }

        $this->setRequest($request);

        $this->_result = $this->_getQuotes();

        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
        $this->_rawRequest = Mage::getSingleton('Webshopapps_Despatchbay_Model_Carrier_Convert_ShipperMapper')->getShipperTranslation($request);

        return $this;
    }

    protected function _getQuotes()
    {
        $resultSet = $this->_getShipperInstance()->getRates($this->_rawRequest, $this->_helper->getRateGatewayUrl());

        $this->_debug($resultSet);

        return $this->_parseShipperResponse($resultSet['result']);
    }

    protected function _parseShipperResponse($shipperResponse)
    {
        // If no rates are found return error message
        $noRates = !isset($shipperResponse->carrierRates);
        $errors = $shipperResponse->errors;

        if (!is_object($shipperResponse) || $noRates || count($errors)) {
            $this->_debug('Unable to parse response');

            if ($noRates) {
                $this->_debug('No rates/carriers have been found');
            }

            if (!is_null($errors)) {
                $this->_debug($errors);
            }

            return $this->returnGeneralError();
        }

        if (isset($shipperResponse->carrierRates)) {
            $carrierRates = $shipperResponse->carrierRates;
        } else {
            $carrierRates = array();
        }
        $result = Mage::getModel('shipping/rate_result');

        // Display rates on the front end, if rates are returned.
        foreach ($carrierRates as $carrierRate) {
            if (isset($carrierRate->errorDetails) || !is_array($carrierRate->rates)) {
                $this->appendError($result, $carrierRate->errorDetails);
                continue;
            }

            $allowedMethods = explode(",", $this->getConfigData('allowed_methods'));

            $costArr = array();
            $priceArr = array();

            $carrierCode = $carrierRate->carrierCode;
            $carrierTitle = $carrierRate->carrierTitle;

            foreach ($carrierRate->rates as $rate) {
                if(in_array($rate->code,$allowedMethods)){
                    $priceArr[$rate->code] = array(
                        'price' => $rate->totalCharges,
                        'title' => $rate->name
                    );
                    $costArr[$rate->code] = $rate->price;
                }
            }

            if (empty($priceArr)) {
                $error = Mage::getModel('shipping/rate_result_error');
                $error->setCarrier($carrierCode);
                $error->setCarrierTitle($carrierTitle);
                $errorMessage = property_exists($carrierRate, '$carrierRate') ?
                    $carrierRate->error_message : $this->getConfigData('specificerrmsg');
                $error->setErrorMessage($errorMessage);
                $result->append($error);
            } else {
                foreach ($priceArr as $methodCode => $rateDetails) {
                    $rate = Mage::getModel('shipping/rate_result_method');
                    $rate->setCarrier($carrierCode);
                    $rate->setCarrierTitle($carrierTitle);
                    // $methodCombineCode = $carrierCode . '_' . $methodCode;
                    $methodCombineCode = preg_replace('/&|;| /', "_", $methodCode);
                    $rawMethodCode = trim($methodCode,'DB_');
                    $carrierName = $this->getCarrierName($rawMethodCode);
                    $rate->setMethod($methodCombineCode);
                    $rate->setMethodTitle($carrierName.' '.$this->_helper->__($rateDetails['title']));
                    $rate->setCost($costArr[$methodCode]);
                    $rate->setPrice($this->getFinalPriceWithHandlingFee($rateDetails['price']));
                    $result->append($rate);
                }
            }
        }

        $this->_debug($result);

        return $result;
    }

    protected function returnGeneralError()
    {
        $result = Mage::getModel('shipping/rate_result');

        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
        $result->append($error);

        return $result;
    }

    protected function appendError($result, $errorDetails)
    {
        $error = Mage::getModel('shipping/rate_result_error');
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage($this->getConfigData('specificerrmsg'));
        $result->append($error);

        return $result;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function _getShipperInstance()
    {
        if (empty($this->_shipperWSInstance)) {
            $this->_shipperWSInstance = new Shipper_Shipper();
        }

        return $this->_shipperWSInstance;
    }

    /**
     * Call to ShipperHQ to get the methods available to Despatch Bay
     *
     * @return array
     */
    public function refreshMethods()
    {
        $allowedMethodUrl = $this->_helper->getAllowedMethodGatewayUrl();

        $shipperMapper = Mage::getSingleton('Webshopapps_Despatchbay_Model_Carrier_Convert_ShipperMapper');
        $resultSet = $this->_getShipperInstance()->getAllowedMethods($shipperMapper->getCredentialsTranslation(), $allowedMethodUrl);
        $this->_debug($resultSet);

        $allowedMethodResponse = $resultSet['result'];

        if (!is_object($allowedMethodResponse) || !isset($allowedMethodResponse->carrierMethods)) {
            $this->_debug('Unable to parse response');

            return array();
        }

        $returnedMethods = $allowedMethodResponse->carrierMethods;

        $allMethods = array();
        $carrierTitles = array();
        foreach ($returnedMethods as $carrierMethod) {

            $rateMethods = $carrierMethod->methods;

            foreach ($rateMethods as $method) {

                $methodCode = $method->methodCode;
                $methodCode = preg_replace('/&|;| /', "_", $methodCode);

                if (!array_key_exists($methodCode, $allMethods)) {
                    $allMethods[$methodCode] = $method->name;
                }
            }

            $carrierTitles[$carrierMethod->carrierCode] = $carrierMethod->title;
        }
        $this->_debug($allMethods);

        // go set carrier titles
        $this->setCarrierTitles($carrierTitles);
        $encodedMethods = json_encode($allMethods);
        $this->setAllMethods($encodedMethods);

        return $allMethods;
    }

    /**
     * Get the saved allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = array();

        foreach ($allowed as $k) {
            $arr[$k] = $k;
        }

        return $arr;
    }

    /**
     * Calls refreshMethods which also updates the carrier title
     *
     * @return bool
     */
    public function refreshCarriers()
    {
        if (!count($this->refreshMethods())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Try to get previously saved shipping methods stored in all_methods global config.
     * If that's empty refresh shipping methods.
     *
     * @return mixed Json object containing all shipping methods
     */
    public function getAllMethods()
    {
        $allMethods = json_decode($this->getConfigData('all_methods'));

        if(!count($allMethods) || empty($allMethods)){
          return $this->refreshMethods();
        }

        return $allMethods;
    }

    /**
     * Set the carrier title that is returned from shipper hq
     *
     * @param $carrierTitles
     */
    protected function setCarrierTitles($carrierTitles)
    {
        foreach ($carrierTitles as $carrierCode => $carrierTitle) {
            $this->_helper->saveCarrierTitle($carrierCode, $carrierTitle);
        }
    }

    /**
     * Save all methods to global config to save a call to ShipperHQ
     *
     * @param $methods
     */
    protected function setAllMethods($methods)
    {
        $this->_helper->saveAllMethods($methods);
    }

    /**
     * Check if carrier has shipping label option available
     *
     * @return boolean
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    /**
     * Retrieve DespatchBay api access from global config
     *
     * @return array
     */
    protected function getAccessCredentials()
    {
        return array(
            'login' => $this->getConfigData('despatch_api_user'),
            'password' => $this->getConfigData('despatch_api_key')
        );
    }

    /**
     * @param      $wsdl
     * @param bool $trace
     * @param      $type
     * @return SoapClient
     */
    protected function _createSoapClient($wsdl, $trace = false, $type = self::SHIPMENT)
    {
        $credentials = $this->getAccessCredentials();

        $client = new SoapClient($wsdl, array('trace'      => $trace,
                                              'login'      => $credentials['login'],
                                              'password'   => $credentials['password'],
                                              'user_agent' => 'ShipperHQ/1.0'));

        switch ($type) {
            case self::TRACK:
                $client->__setLocation(self::TRACK_URL);
                break;
            case self::SHIPMENT:
                $client->__setLocation(self::SHIPMENT_URL);
                break;
            default:
                $client->__setLocation(self::SHIPMENT_URL);
                break;
        }

        return $client;
    }

    /**
     * Create shipment soap client
     *
     * @return SoapClient
     */
    protected function _createShipmentSoapClient()
    {
        return $this->_createSoapClient($this->_shipmentServiceWsdl, $this->_debug, self::SHIPMENT);
    }

    protected function _createTrackingSoapClient()
    {
        return $this->_createSoapClient($this->_trackServiceWsdl, $this->_debug, self::TRACK);
    }

    protected function _setAccessRequest(&$r){
        $r->setApiUser($this->getConfigData('despatch_api_user'));
        $r->setApiKey($this->getConfigData('despatch_api_key'));
    }

    /**
     * Do request to shipment
     *
     * @param Mage_Shipping_Model_Shipment_Request $request
     * @return array
     */
    public function requestToShipment(Mage_Shipping_Model_Shipment_Request $request)
    {
        $packages = $request->getPackages();

        if (!is_array($packages) || !$packages) {
            Mage::throwException(Mage::helper('usa')->__('No packages for request'));
        }

        if ($request->getStoreId() != null) {
            $this->setStore($request->getStoreId());
        }

        $data = array();
        $result = array();

        foreach ($packages as $packageId => $package) {
            if($package['params']['weight'] > 30){
                Mage::throwException(Mage::helper('usa')->__('Package #'.$packageId.' Exceeds 30Kg Max Weight'));
            }
            $request->setPackageId($packageId);
            $request->setServiceId(trim($package['params']['container'], 'despatchbay#'));
            $request->setPackageWeight($package['params']['weight']);
            $request->setPackageValue($package['params']['customs_value']);
            $request->setPackageParams(new Varien_Object($package['params']));
            $request->setPackageItems($package['items']);

            $result = $this->_doShipmentRequest($request);

            if ($result->hasErrors()) {
                $this->rollBack($data);
                break;
            } else {
                $data[] = array(
                    'tracking_number' => $result->getTrackingNumber(),
                    'label_content'   => $result->getShippingLabelContent()
                );
            }

            if (!isset($isFirstRequest)) {
                $request->setMasterTrackingId($result->getTrackingNumber());
                $isFirstRequest = false;
            }
        }

        $response = new Varien_Object(array('info' => $data));

        if ($result->getErrors()) {
            $response->setErrors($result->getErrors());
        }

        return $response;
    }

    public function rollBack($data)
    {
        return true;
    }

    /**
     * Do shipment request to Despatch Bay, creates a new shipment and returns shipment ID in response
     *
     * @param Varien_Object $request
     * @return Varien_Object
     */
    protected function _doShipmentRequest(Varien_Object $request)
    {
        $this->_prepareShipmentRequest($request);
        $result = new Varien_Object();
        $response = null;

        try {
            $client = $this->_createShipmentSoapClient();
            $requestShip = $this->_formShipmentRequest($request);
            $response = $client->AddDomesticShipment($requestShip);//crete shipment on DS.com
            $debugData['request_sent'] = $requestShip;
            $debugData['response'] = "Shipment ID: " . $response;
        } catch (Exception $e) {
            $debugData['result'] = array(
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            );
            $result->setErrors($e->getMessage());
        }

        $this->_debug($debugData);

        return $this->_sendShipmentAcceptRequest($response);
    }

    /**
     * Use the shipment id to retreive the new shipment from Despatch Bay and set the label and tracking number on
     * the result
     *
     * @param $shipmentId
     * @return Varien_Object
     */
    protected function _sendShipmentAcceptRequest($shipmentId)
    {
        $result = $this->generateLabels($shipmentId);

        if($result->hasErrors()) {
            return $result;
        }

        $shipment = new Varien_Object();

        try {
            $client = $this->_createShipmentSoapClient();
            $shipment = $client->GetShipment($shipmentId);
            $debugData['request_sent'] = $shipmentId;
            $debugData['response'] = $shipment;
        } catch (Exception $e) {
            $debugData['result'] = array(
                'error' => $e->getMessage(),
                'code'  => $e->getCode()
            );
        }

        $result->setTrackingNumber($shipment->StartTrackingNumber);

        $this->_debug($debugData);

        return $result;
    }

    /**
     * Takes the Despatch Bay shipment ID and creates labels for it
     *
     * @param $shipmentId
     * @return Varien_Object
     */
    protected function generateLabels($shipmentId)
    {
        $client = new Varien_Http_Client(self::LABEL_URL);
        $client->setMethod(Varien_Http_Client::POST);
        $client->setConfig(array('useragent' => 'ShipperHQ/1.0'));
        $accessDetails = $this->getAccessCredentials();
        $client->setParameterPost('apikey', $accessDetails['password']);
        $client->setParameterPost('apiuser', $accessDetails['login']);
        $client->setParameterPost('format', $this->getConfigData('label_size'));
        $client->setParameterPost('sid', $shipmentId);
        $result = new Varien_Object();

        try {
            $labelResponse = $client->request();

            $debugData['request'] = $client->getLastRequest();
            $debugData['response'] = $client->getLastResponse();
            $this->_debug($debugData);

            if($labelResponse->isSuccessful()) {
                $result->setShippingLabelContent($labelResponse->getBody());
            } else {
                $result->setErrors($labelResponse->getMessage() .' - '. $labelResponse->getBody());
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $result;
    }

    /**
     * Form array with appropriate structure for shipment request.
     * Weight is irrelevant. Each package can weight upto 30Kg and is charged the same.
     *
     * @param Varien_Object $request
     * @return array
     */
    protected function _formShipmentRequest(Varien_Object $request)
    {
        $this->_request = $request;

        $shipRequest = new StdClass();

        $shipRequest->ServiceID             = $request->getServiceId();
        $shipRequest->ParcelQuantity        = 1;//count($request->getPackages());
        $shipRequest->OrderReference        = 'Order #'.$request->getOrderShipment()->getOrder()->getIncrementId();
        $shipRequest->Contents              = self::createItemsArray($request->getPackageItems());
        $shipRequest->RecipientName         = $request->getRecipientContactPersonName();
        $shipRequest->Street                = $request->getRecipientAddressStreet();
        $shipRequest->Locality              = $request->getRecipientAddressStreet2();
        $shipRequest->Town                  = $request->getRecipientAddressCity();
        $shipRequest->County                = $request->getRecipientAddressStateOrProvinceCode();
        $shipRequest->Postcode              = $request->getRecipientAddressPostalCode();
        $shipRequest->RecipientEmail        = $request->getRecipientEmail();
        $shipRequest->EmailNotification     = $this->getConfigFlag('shipment_email');
        $shipRequest->DashboardNotification = $this->getConfigFlag('shipment_dashboard');

        return $shipRequest;
    }

    private function createItemsArray($items)
    {
        $itemString = "";

        foreach ($items as $item) {
            $itemString .= $item['name'].',';
        }

        if(strlen($itemString) > 255) {
            $itemString = substr($itemString, 0, 255);
        }

        return trim($itemString,',');
    }

    /**
     * Prepare shipment request.
     * Validate and correct request information
     *
     * @param Varien_Object $request
     *
     */
    protected function _prepareShipmentRequest(Varien_Object $request)
    {
        $phonePattern = '/[\s\_\-\(\)]+/';
        $phoneNumber = $request->getShipperContactPhoneNumber();
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);

        $request->setShipperContactPhoneNumber($phoneNumber);

        $phoneNumber = $request->getRecipientContactPhoneNumber();
        $phoneNumber = preg_replace($phonePattern, '', $phoneNumber);

        $request->setRecipientContactPhoneNumber($phoneNumber);
    }

    public function getTracking($trackings)
    {
        $this->setTrackingReqeust();

        if (!is_array($trackings)) {
            $trackings=array($trackings);
        }

        foreach($trackings as $tracking){
            $this->_getSoapTracking($tracking);
        }

        return $this->_result;
    }

    protected function _getSoapTracking($trackingNumber)
    {
        $response = array();
        try {
            $client = $this->_createTrackingSoapClient();
            $response = $client->GetTracking($trackingNumber);
            $debugData['request_sent'] = $trackingNumber;
            $debugData['response'] = $response;
        } catch (Exception $e) {
            $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
            Mage::logException($e);
            $response['error'] = $e->getMessage();
        }

        $this->_debug($debugData);
        $this->_parseTrackingResponse($trackingNumber, $response);
    }

    protected function _parseTrackingResponse($trackingNumber, $response)
    {
        $errorTitle=null;

        if (count($response)) {
            if (array_key_exists('error',$response)) {
                $errorTitle = $response['error'];
            } else {
                $resultArray = array();
                $packageProgress = array();

                $resultCount = 0;

                foreach ($response as $responseObject) {
                    if($resultCount > 0) {
                        $tempArray = array();
                        $tempArray['deliverydate'] = $responseObject->Date;
                        if(property_exists($responseObject,'Time')){
                            $tempArray['deliverytime'] = $responseObject->Time;
                        }
                        $tempArray['activity'] = $responseObject->Description;
                        $tempArray['deliverylocation'] = $responseObject->Location;
                        $packageProgress[] = $tempArray;
                    } else {
                        $resultArray['status'] = $responseObject->Description;
                        $resultArray['deliverydate'] = $responseObject->Date;
                        $resultArray['deliverytime'] = $responseObject->Time;
                        $resultArray['deliverylocation'] = $responseObject->Location;
                        $resultArray['signedby'] = $responseObject->Signatory;
                    }
                    $resultCount++;
                }

                $resultArray['progressdetail'] = $packageProgress;
            }
        }

        if (!$this->_result) {
            $this->_result = Mage::getModel('shipping/tracking_result');
        }

        if (isset($resultArray)) {
            $tracking = Mage::getModel('shipping/tracking_result_status');
            $tracking->setCarrier('webshopapps_despatchbay');
            $tracking->setCarrierTitle($this->getConfigData('title'));
            $tracking->setTracking($trackingNumber);
            $tracking->addData($resultArray);
            $this->_result->append($tracking);
        } else {
            $error = Mage::getModel('shipping/tracking_result_error');
            $error->setCarrier('webshopapps_despatchbay');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setTracking($trackingNumber);
            $error->setErrorMessage($errorTitle ? $errorTitle : Mage::helper('usa')->__('Unable to retrieve tracking'));
            $this->_result->append($error);
        }
    }

    /**
     * Return Despatch Bay services with prices included
     *
     * @param Varien_Object|null $params
     * @return array
     */
    public function getContainerTypes(Varien_Object $params = null)
    {
        if(!is_null($params)) {
            $postalcode = $params['postalcode_recipient'];

            return $this->getDomesticServicesByPostcode($postalcode);
        }

        return $this->getCode('packaging');
    }

    public function getCustomizableContainerTypes()
    {
        return array('none' => 'none');
    }

    public function isGirthAllowed($countyDest = null)
    {
        return false;
    }

    /**
     * Get all services available to given postal code with pricing
     *
     * @param $postcode
     * @return array
     */
    public function getDomesticServicesByPostcode($postcode)
    {
        $requestString = $postcode;
        $response = $this->_getCachedQuotes($requestString);
        $debugData = array('request' => $requestString);

        if($response === null) {
            try {
                $client = $this->_createShipmentSoapClient();
                $response = $client->GetDomesticServicesByPostcode($requestString);
                $this->_setCachedQuotes($requestString, serialize($response));
                $debugData['result'] = $response;
            } catch (Exception $e) {
                $debugData['result'] = array('error' => $e->getMessage(), 'code' => $e->getCode());
                Mage::logException($e);
            }
        } else {
            $response = unserialize($response);
            $debugData['result'] = $response;
        }

        $this->_debug($debugData);

        return $this->_parseDomesticServicesByPostcodeRequest($response);
    }

    /**
     * Sort the available services into a array
     * Array key is service code
     *
     * @param $response
     * @return array
     */
    protected function _parseDomesticServicesByPostcodeRequest($response)
    {
        $result = array();

        if (count($response)) {
            foreach ($response as $method) {
                $price = $this->getPriceAndFormat($method->Cost);
                $serviceId = $method->ServiceID;
                $carrierName = $this->getCarrierName($serviceId);
                $result[$serviceId] = $carrierName .' '.$method->Name .' '. $price;
            }
        }

        return $result;
    }

    private function getPriceAndFormat($price)
    {
        $finalPrice = $this->getFinalPriceWithHandlingFee($price);

        $price = Mage::helper('core')->currency($finalPrice, true, false);

        return $price;
    }

    public function getCarrierName($serviceId)
    {
        switch ($serviceId){
            case $serviceId == 47: return 'Despatch Bay';
            case $serviceId == 48: return 'Despatch Bay';
            case $serviceId <= 14: return 'Yodel';
            case $serviceId <= 49: return 'ParcelForce';
            case $serviceId >  50: return 'CityLink';

            default: return '';
        }
    }

    /**
     * @param        $type
     * @param string $code
     * @return bool|array
     */
    public function getCode($type, $code = '')
    {
        $codes = array(
            'packaging' => array(
                'custom_package' => Mage::helper('webshopapps_despatchbay')->__('Your Packaging (Max Length 1.5m - Max Length and Girth 3m)')
            ),
            'label_formats' => array(
                '1A4'   => $this->_helper->__('One Label per A4 Sheet'),
                '1A6'   => $this->_helper->__('A6 Format Labels'),
                '2A4'   => $this->_helper->__('Two Labels per A4 Sheet'),
            )
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
}
