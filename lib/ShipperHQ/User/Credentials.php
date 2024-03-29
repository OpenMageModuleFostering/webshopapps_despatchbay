<?php
/**
 *
 * Webshopapps Shipping Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Shipping_Carrier
 * @copyright Copyright (c) 2014 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
namespace ShipperHQ\User;

/**
 * Class Credentials
 *
 * @package ShipperHQ\User
 */
class Credentials
{
    public $userId;
    public $password;
    public $apiKey;

   /**
    * @param null $apiKey
    * @param null $password
    * @param null $userId
    */
   function __construct($apiKey = null, $password = null, $userId = null)
   {
      $this->apiKey = $apiKey;
      $this->password = $password;
      $this->userId = $userId;
   }

   /**
    * @param mixed $apiKey
    */
   public function setApiKey($apiKey)
   {
      $this->apiKey = $apiKey;
   }

   /**
    * @return mixed
    */
   public function getApiKey()
   {
      return $this->apiKey;
   }

   /**
    * @param mixed $password
    */
   public function setPassword($password)
   {
      $this->password = $password;
   }

   /**
    * @return mixed
    */
   public function getPassword()
   {
      return $this->password;
   }

   /**
    * @param mixed $userId
    */
   public function setUserId($userId)
   {
      $this->userId = $userId;
   }

   /**
    * @return mixed
    */
   public function getUserId()
   {
      return $this->userId;
   }
}
