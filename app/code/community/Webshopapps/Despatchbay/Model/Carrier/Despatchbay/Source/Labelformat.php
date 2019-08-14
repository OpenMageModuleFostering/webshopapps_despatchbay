<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_despatchbay
 * User         Joshua Stewart
 * Date         25/04/2014
 * Time         17:47
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Model_Carrier_Despatchbay_Source_Labelformat
{
    public function toOptionArray()
    {
        $despatchBayModel = Mage::getSingleton('webshopapps_despatchbay/carrier_despatchbay');

        $arr = array();
        $formats = $despatchBayModel->getCode('label_formats');

        if($formats){
            foreach ($formats as $code=>$format) {
                $arr[] = array('value'=>$code, 'label'=>$format);
            }
        }

        return $arr;
    }
}