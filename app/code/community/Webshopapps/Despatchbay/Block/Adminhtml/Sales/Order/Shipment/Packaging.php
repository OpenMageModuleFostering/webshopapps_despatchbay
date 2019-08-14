<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_despatchbay
 * User         Joshua Stewart
 * Date         16/04/2014
 * Time         15:54
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Block_Adminhtml_Sales_Order_Shipment_Packaging
    extends Mage_Adminhtml_Block_Sales_Order_Shipment_Packaging
{
    /**
     * Override this method to allow Despatch Bay for orders that used a alternative carrier
     *
     * @return array
     */
    public function getContainers()
    {
        if(!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Despatchbay', 'carriers/despatchbay/labels')) {
            return parent::getContainers();
        }

        $order   = $this->getShipment()->getOrder();
        $storeId = $this->getShipment()->getStoreId();
        $carrier = $order->getShippingCarrier();
        $shippingMethodCode = trim($order->getShippingMethod(), 'despatchbay_DB_');
        $arrayKeyCode = 'despatchbay#'.$shippingMethodCode;

        if ($carrier) {
            $params = new Varien_Object(
                array(
                    'method' => $order->getShippingMethod(true)->getMethod(),
                    'postalcode_recipient' => $order->getShippingAddress()->getPostcode(),
                )
            );

            $despatchbayCarrier = Mage::getModel('webshopapps_despatchbay/carrier_despatchbay');
            $containerTypes[$despatchbayCarrier->getCarrierCode()] = $despatchbayCarrier->getContainerTypes($params);

            if($carrier->getCarrierCode() != Mage::getModel('webshopapps_despatchbay/carrier_despatchbay')->getCarrierCode()) {
                $containerTypes[$carrier->getCarrierCode()] =  $carrier->getContainerTypes();
            }

            $containers = array();

            foreach ($containerTypes as $carrierCode=>$containerArray) {
                $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title', $storeId);
                foreach($containerArray as $containerCode=>$containerName) {
                    $containers[$carrierCode.'#'.$containerCode] =  $carrierTitle .' : ' .$containerName;
                }
            }

            //Set the first element of the array to the chosen shipping method
            if(array_key_exists($arrayKeyCode,$containers)){
                $containers = array($arrayKeyCode => $containers[$arrayKeyCode]) + $containers;
            }

            $arr = $containers;

            return $arr;
        }
        return array();
    }

    /**
     * Override this method to disable the dimensional input for Despatch Bay boxes
     *
     * @return array
     */
    protected function _getCustomizableContainers()
    {
        if(!Mage::helper('wsacommon')->isModuleEnabled('Webshopapps_Despatchbay', 'carriers/despatchbay/labels')) {
            return parent::_getCustomizableContainers();
        }

        $carrier = $this->getShipment()->getOrder()->getShippingCarrier();
        $types = array();
        if ($carrier) {
            $types = $carrier->getCustomizableContainerTypes();
        }

        if(count($types)) {
            return $types;
        } else {
            return Mage::getModel('webshopapps_despatchbay/carrier_despatchbay')->getCustomizableContainerTypes();
        }
    }
}