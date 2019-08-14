<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_despatchbay
 * User         Joshua Stewart
 * Date         28/04/2014
 * Time         15:57
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Block_Adminhtml_Carrier_About extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{
    /**
     * Return header comment part of html for fieldset
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getHeaderCommentHtml($element)
    {
        $beforeDiv = '<div style="padding:10px;background-color:#fff;border:1px solid #ddd;margin-bottom:7px;">';
        $dbHtml  = '<div class= "location left"><img style="height: 36px; padding: 0px 0px;" class="logo" src="'.$this->getSkinUrl('wsa/images/despatch_bay_pro_logo.png') .'"/></div>';
        $wsaHtml = '<div class= "location left"><img style="height: 36px; padding: 0px 0px;" class="logo" src="'.$this->getSkinUrl('wsa/images/WSA_OfficialLogo_HorizontalLockup.png') .'"/></div>';
        $wsaText = Mage::helper('webshopapps_despatchbay')->__('This extension is provided by WebShopApps, developers of Powerful Shipping Solutions for Magento.<br />
        Visit <a href="http://www.webshopapps.com" target="_blank">www.WebShopApps.com</a> to browse our catalog of extensions and learn more about us.<br />');

        $afterDiv = '</div>';
        $wsa = '<div class="comment">' .$wsaHtml .$beforeDiv  .$wsaText .$afterDiv;
        $comment =  $element->getComment()
            ?  $dbHtml .$beforeDiv  .$element->getComment() .$afterDiv .'</div>'
            : '</div>';
        return $wsa .$comment;
    }
}