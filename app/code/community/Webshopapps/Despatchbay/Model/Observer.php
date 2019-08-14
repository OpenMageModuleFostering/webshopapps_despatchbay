<?php

/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         16/04/2014
 * Time         12:58
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Sets the shipping method on the shipment
     *
     * @param $observer
     * @return null
     */
    public function adminhtmlControllerActionPredispatchStart()
    {
        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Despatchbay', 'carriers/despatchbay/labels')) {
            return null;
        }

        if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2hpcF9vbmNl', 'dHVyYm9tb2Rl', 'Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2VyaWFs')) {
            Mage::helper('wsalogger/log')->postCritical('Webshopapps Despatch Bay', base64_decode('TGljZW5zZQ=='), base64_decode('U2VyaWFsIEtleSBJbnZhbGlk'));

            return null;
        }

        $request = Mage::app()->getFrontController()->getRequest();
        $params = $request->getParams();
        if (strstr($request->getControllerName(), 'sales_order_shipment') && strstr($request->getActionName(), 'createLabel')) {
            if(!self::isValidCountry($params)) {
                return null;
            }

            $request = Mage::app()->getRequest();
            $request->initForward()
                ->setControllerName('shipment')
                ->setModuleName('webshopapps_despatchbay')
                ->setActionName('createDespatchbayLabel')
                ->setDispatched(false);
        } elseif (strstr($request->getControllerName(), 'sales_order_shipment') && strstr($request->getActionName(), 'save')) {
            if(!self::isValidCountry($params)) {
                return null;
            }

            $request = Mage::app()->getRequest();
            $request->initForward()
                ->setControllerName('shipment')
                ->setModuleName('webshopapps_despatchbay')
                ->setActionName('save')
                ->setDispatched(false);
        }
    }

    /**
     * Takes request params and gets shipment country. If not UK then Despatch is not available
     *
     * @param $params
     * @return bool
     */
    private function isValidCountry($params)
    {
        if(!array_key_exists('shipment_id',$params)) {
            return false;
        }

        $countryId = Mage::getModel('sales/order_shipment')->load($params['shipment_id'])->getShippingAddress()->getCountryId();

        if($countryId != 'GB') {
            return false;
        }

        return true;
    }

    public function coreBlockAbstractToHtmlBefore($observer)
    {
        if (!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Despatchbay', 'carriers/despatchbay/labels')) {
            return;
        }

        if ($observer->getBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View_Form) {
            if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2hpcF9vbmNl', 'dHVyYm9tb2Rl', 'Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2VyaWFs')) {
                Mage::helper('wsalogger/log')->postCritical('Webshopapps Despatch Bay', base64_decode('TGljZW5zZQ=='), base64_decode('U2VyaWFsIEtleSBJbnZhbGlk'));

                return null;
            }

            $params = Mage::app()->getFrontController()->getRequest()->getParams();

            $countryId = Mage::getModel('sales/order_shipment')->load($params['shipment_id'])->getShippingAddress()->getCountryId();

            if($countryId != 'GB') {
                return null;
            }

            $observer->getBlock()->setTemplate('webshopapps_despatchbay/sales/order/shipment/view/form.phtml');
        } elseif ($observer->getBlock() instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Items) {
            if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2hpcF9vbmNl', 'dHVyYm9tb2Rl', 'Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2VyaWFs')) {
                Mage::helper('wsalogger/log')->postCritical('Webshopapps Despatch Bay', base64_decode('TGljZW5zZQ=='), base64_decode('U2VyaWFsIEtleSBJbnZhbGlk'));

                return null;
            }

            $params = Mage::app()->getFrontController()->getRequest()->getParams();

            $countryId = Mage::getModel('sales/order')->load($params['order_id'])->getShippingAddress()->getCountryId();

            if($countryId != 'GB') {
                return null;
            }

            $observer->getBlock()->setTemplate('webshopapps_despatchbay/sales/order/shipment/create/items.phtml');
        }
    }

    public function postError()
    {
        if (!Mage::helper('wsacommon')->checkItems('Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2hpcF9vbmNl', 'dHVyYm9tb2Rl', 'Y2FycmllcnMvZGVzcGF0Y2hiYXkvc2VyaWFs')) {
            $session = Mage::getSingleton('adminhtml/session');
            $session->addError(Mage::helper('adminhtml')->__(base64_decode('U2VyaWFsIGtleSBpcyBub3QgdmFsaWQgZm9yIFdlYnNob3BhcHBzIERlc3BhdGNoIEJheSA=')));
        }
    }
}