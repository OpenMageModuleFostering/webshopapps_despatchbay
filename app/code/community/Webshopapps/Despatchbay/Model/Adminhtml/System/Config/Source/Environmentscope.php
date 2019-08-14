<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         14/04/2014
 * Time         18:06
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Adminhtml_System_Config_Source_Environmentscope
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Shipper_Shipper::LIVE,
                'label' => Mage::helper('webshopapps_despatchbay')->__('Live')
            ),
            array(
                'value' => Shipper_Shipper::DEV,
                'label' => Mage::helper('webshopapps_despatchbay')->__('Development')
            ),
            array(
                'value' => Shipper_Shipper::TEST,
                'label' => Mage::helper('webshopapps_despatchbay')->__('Test')
            ),
            array(
                'value' => Shipper_Shipper::INTEGRATION,
                'label' => Mage::helper('webshopapps_despatchbay')->__('Integration')
            ),
        );
    }
}