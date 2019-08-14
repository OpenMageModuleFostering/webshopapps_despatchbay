<?php

namespace ShipperHQ\WS\Request\Rate;
use ShipperHQ\Shipping\Address;

/**
 * Class RateRequest
 *
 * @package ShipperHQ\WS\Request\Rate
 */
class RateRequest extends \ShipperHQ\WS\Request\AbstractWebServiceRequest implements \ShipperHQ\WS\Request\WebServiceRequest
{

   public $cart;
   public $origin;
   public $destination;

   /**
    * @param null $cart
    * @param Address $destination
    * @param Address $origin
    */
   function __construct($cart = null, \ShipperHQ\Shipping\Address $destination = null, \ShipperHQ\Shipping\Address $origin = null)
   {
      $this->cart = $cart;
      $this->destination = $destination;
      $this->origin = $origin;
   }

   /**
    * @param mixed $cart
    */
   public function setCart($cart)
   {
      $this->cart = $cart;
   }

   /**
    * @return mixed
    */
   public function getCart()
   {
      return $this->cart;
   }

   /**
    * @param Address $destination
    */
   public function setDestination(\ShipperHQ\Shipping\Address $destination)
   {
      $this->destination = $destination;
   }

   /**
    * @return Address
    */
   public function getDestination()
   {
      return $this->destination;
   }

   /**
    * @param Address $origin
    */
   public function setOrigin(\ShipperHQ\Shipping\Address $origin)
   {
      $this->origin = $origin;
   }

   /**
    * @return Address
    */
   public function getOrigin()
   {
      return $this->origin;
   }
}