<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         17/04/2014
 * Time         14:14
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Carrier_Despatchbay_Source_Method
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

        return $arr;
    }
}