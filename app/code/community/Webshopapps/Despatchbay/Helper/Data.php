<?php
/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         15/04/2014
 * Time         10:16
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
class Webshopapps_Despatchbay_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Returns url to use - live if present, otherwise dev
     * @return array
     */
    private function getGatewayUrl()
    {
        $path = trim(Mage::getStoreConfig('carriers/despatchbay/url'));
        return rtrim($path, '/') . '/';
    }

    /**
     * Retrieve url for getting allowed methods
     * @return string
     */
    public function getAllowedMethodGatewayUrl()
    {
        return $this->getGatewayUrl().'allowed_methods';
    }

    /**
     * Retrieve url for getting shipping rates
     * @return string
     */
    public function getRateGatewayUrl()
    {
        return $this->getGatewayUrl().'rates';

    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getCheckSynchronizedUrl()
    {
        return $this->getGatewayUrl().'attributes/check';
    }

    /*
     * *Retrieve url for retrieving attributes
     */
    public function getSetSynchronizedUrl()
    {
        return $this->getGatewayUrl().'attributes/set/updated';
    }

    /**
     * Saves the carrier title to core_config_data
     * Need to do this as doesnt read from the shipping rate quote table!
     * @param $carrierCode
     * @param $carrierTitle
     */
    public function saveCarrierTitle($carrierCode,$carrierTitle)
    {
        $this->saveConfig('carriers/'.$carrierCode.'/title',$carrierTitle);
    }

    public function saveAllMethods($methods)
    {
        $this->saveConfig('carriers/despatchbay/all_methods',$methods);
    }

    /**
     * Save config value to db
     * @param $path
     * @param $value
     * @param string $scope
     * @param int $scopeId
     * @return $this
     */
    public function saveConfig($path, $value, $scope = 'default', $scopeId = 0)
    {
        Mage::getConfig()->saveConfig(rtrim($path, '/'), $value, $scope, $scopeId);
    }
}