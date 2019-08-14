<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_despatchbay
 * User         Joshua Stewart
 * Date         01/05/2014
 * Time         15:51
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Carrier_Despatchbay_Source_Freemethod
{
    public function toOptionArray()
    {
        $despatchBayModel = Mage::getSingleton('webshopapps_despatchbay/carrier_despatchbay');

        $arr = array();
        $methods = $despatchBayModel->getAllMethods();

        if($methods){
            foreach ($methods as $code=>$method) {
                $arr[] = array('value'=>$code, 'label'=>$method);
            }
        }

        array_unshift($arr, array('value'=>'', 'label'=>Mage::helper('shipping')->__('None')));

        return $arr;
    }
}