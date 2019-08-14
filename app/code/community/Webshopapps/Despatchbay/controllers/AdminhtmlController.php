<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         15/04/2014
 * Time         15:24
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_AdminhtmlController extends Mage_Adminhtml_Controller_Action
{
    public function refreshcarriersAction()
    {
        $carrier = Mage::getModel('webshopapps_despatchbay/carrier_despatchbay');
        $refreshResult = $carrier->refreshCarriers();
        $success = 0;
        $message =  Mage::helper('webshopapps_despatchbay')->__('There was an issue connecting to ShipperHQ');

        if ($refreshResult) {
            $success = 1;
            $message = Mage::helper('webshopapps_despatchbay')->__('Despatch Bay has been updated via ShipperHQ');
        }

        $result= array('result' =>$success, 'message' =>$message);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}