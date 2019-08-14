<?php

 /**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         14/04/2014
 * Time         17:51
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
include_once 'ShipperHQ/User/Credentials.php';
include_once 'ShipperHQ/User/SiteDetails.php';

class Webshopapps_Despatchbay_Model_Carrier_Convert_ShipperMapper
{
    protected static $ecommerceType = 'magento';
    protected static $_stdAttributeNames = array(
        'shipperhq_free_shipping',
        'shipperhq_shipping_price',
        'shipperhq_additional_price',
        'shipperhq_handling_price',
        'shipperhq_carrier_code',
    );

    /**
     * Set up values for ShipperHQ getRates()
     *
     * @param $magentoRequest
     * @return string
     */
    public static function getShipperTranslation($magentoRequest)
    {
        $shipperHQRequest['credentials'] = self::getCredentials();
        $shipperHQRequest['siteDetails'] = self::getSiteDetails();
        $shipperHQRequest['cart']        = self::getCartDetails($magentoRequest);
        $shipperHQRequest['destination'] = self::getDestination($magentoRequest);
        $shipperHQRequest['origin']      = self::getOrigin($magentoRequest);

        return $shipperHQRequest;
    }

    /**
     * Set up values for ShipperHQ getAllowedMethods()
     *
     * @return string
     */
    public static function getCredentialsTranslation()
    {
        $shipperHQRequest['credentials'] = self::getCredentials();
        $shipperHQRequest['siteDetails'] = self::getSiteDetails();

        return $shipperHQRequest;
    }

    /**
     * Return credentials for ShipperHQ login
     *
     * @return array
     */
    public static function getCredentials()
    {
        $dbUser = Mage::getStoreConfig('carriers/despatchbay/despatch_api_user');
        $dbKey = Mage::getStoreConfig('carriers/despatchbay/despatch_api_key');
        $credentials = new \ShipperHQ\User\Credentials('2bc3bf8ef59577573757096baee2fc99', $dbKey, $dbUser);

        return $credentials;
    }

    /**
     * Format cart for from shipper for Magento
     *
     * @param $request
     * @return array
     */
    public static function getCartDetails($request)
    {
        $cart = array();
        $cart['declaredValue'] = $request->getPackageValue();
        $cart['freeShipping'] = (bool)$request->getFreeShipping();
        $cart['items'] = self::getFormattedItems($request, $request->getAllItems());

        return $cart;
    }

    /**
     * Return site specific information
     *
     * @return array
     */
    public static function getSiteDetails()
    {
        $siteDetails = new \ShipperHQ\User\SiteDetails('Magento ' . Mage::getEdition(), Mage::getVersion(), Mage::getBaseUrl(), Shipper_Shipper::TEST);

        return $siteDetails;
    }

    /**
     * Get values for items
     *
     * @param      $request
     * @param      $magentoItems
     * @param bool $childItems
     * @param null $taxPercentage
     * @return array
     */
    private static function getFormattedItems($request, $magentoItems, $childItems = false, $taxPercentage = null)
    {
        $formattedItems = array();
        if (empty($magentoItems)) {
            return $formattedItems;
        }
        foreach ($magentoItems as $magentoItem) {
            if (!$childItems && $magentoItem->getParentItemId()) {
                continue;
            }

            if (is_null($taxPercentage)) {
                $calculator = Mage::helper('tax')->getCalculator();
                $taxRequest = $calculator->getRateOriginRequest();
                $taxRequest->setProductClassId($magentoItem->getTaxClassId());
                $taxPercentage = $calculator->getRate($taxRequest);
            }

            $formattedItem = array(
                'id'                          => $magentoItem->getId(),
                'name'                        => $magentoItem->getName(),
                'storePrice'                  => $magentoItem->getPrice(),
                'weight'                      => $magentoItem->getWeight(),
                'qty'                         => $magentoItem->getQty(),
                'type'                        => $magentoItem->getProductType(),
                'items'                       => array(),
                // child items
                'basePrice'                   => $magentoItem->getBasePrice(),
                'taxInclBasePrice'            => $magentoItem->getBasePriceInclTax(),
                'taxInclStorePrice'           => $magentoItem->getPriceInclTax(),
                'rowTotal'                    => $magentoItem->getRowTotal(),
                'baseRowTotal'                => $magentoItem->getBaseRowTotal(),
                'discountPercent'             => $magentoItem->getDiscountPercent(),
                'discountedBasePrice'         => $magentoItem->getBasePrice() - $magentoItem->getBaseDiscountAmount(),
                'discountedStorePrice'        => $magentoItem->getPrice() - $magentoItem->getDiscountAmount(),
                'discountedTaxInclBasePrice'  => $magentoItem->getBasePriceInclTax() - $magentoItem->getBaseDiscountAmount(),
                'discountedTaxInclStorePrice' => $magentoItem->getPriceInclTax() - $magentoItem->getDiscountAmount(),
                'attributes'                  => array(),
                'legacyAttributes'            => array(),
                'baseCurrency'                => $request->getBaseCurrency()->getCurrencyCode(),
                'packageCurrency'             => $request->getPackageCurrency()->getCurrencyCode(),
                'storeBaseCurrency'           => Mage::app()->getBaseCurrencyCode(),
                'storeCurrentCurrency'        => Mage::app()->getStore()->getCurrentCurrencyCode(),
                'taxPercentage'               => $taxPercentage,
                'freeShipping'                => $magentoItem->getFreeShipping(),
                'freeMethodWeight'            => $request->getFreeMethodWeight(),
                'additionalAttributes'        => array(),
                'fixedPrice'                  => $magentoItem->getFixedPrice(),
                'fixedWeight'                 => $magentoItem->getFixedWeight(),
            );

            if (!$childItems) {
                $formattedItem['items'] = self::getFormattedItems($request, $magentoItem->getChildren(), true, $taxPercentage);
            }

            $formattedItems[] = $formattedItem;
        }

        return $formattedItems;
    }

    /**
     * Get values for destination
     *
     * @param $request
     * @return array
     */
    private static function getDestination($request)
    {
        $destination = array(
            'country'     => $request->getDestCountryId(),
            'region'      => $request->getDestRegionCode(),
            'city'        => $request->getDestCity(),
            'street'      => $request->getDestStreet(),
            'zipcode'     => $request->getDestPostcode(),
            'residential' => $request->getShiptoType(),
        );

        return $destination;
    }

    /**
     * Get values for origin
     *
     * @param $request
     * @return array
     */
    private static function getOrigin($request)
    {
        $regionModel = Mage::getModel('directory/region')->load($request->getRegionId());
        $regionCode = $regionModel->getCode();

        $origin = array(
            'country' => $request->getCountryId(),
            'region'  => $regionCode,
            'city'    => $request->getCity(),
            'street'  => $request->getStreet(),
            'zipcode' => $request->getPostcode(),
        );

        return $origin;
    }

    private static function getShortDescripion($magentoItem)
    {
        return Mage::getModel('catalog/product')->load($magentoItem->getProduct()->getId())->getShortDescription();
    }
}