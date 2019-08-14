<?php

/**
 * WebShopApps Shipping Module
 *
 * @category    WebShopApps
 * @package     WebShopApps_Despatchbay
 * User         Joshua Stewart
 * Date         16/04/2014
 * Time         14:35
 * @copyright   Copyright (c) 2014 Zowta Ltd (http://www.WebShopApps.com)
 *              Copyright, 2014, Zowta, LLC - US license
 * @license     http://www.WebShopApps.com/license/license.txt - Commercial license
 *
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';

class Webshopapps_Despatchbay_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
    public function createDespatchbayLabelAction()
    {
        $response = new Varien_Object();
        try {
            $shipment = $this->_initShipment();
            if ($this->_createShippingLabel($shipment)) {
                $shipment->save();
                $this->_getSession()->addSuccess(Mage::helper('sales')->__('The shipping label has been created.'));
                $response->setOk(true);
            }
        } catch (Mage_Core_Exception $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $response->setError(true);
            $response->setMessage(Mage::helper('sales')->__('An error occurred while creating shipping label.'));
        }

        $this->getResponse()->setBody($response->toJson());
    }

    /**
     * @return null|void
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost('shipment');
        $isNeedCreateLabel = false;
        $responseAjax = new Varien_Object();
        $shipment = false;

        if (!empty($data['comment_text'])) {
            Mage::getSingleton('adminhtml/session')->setCommentText($data['comment_text']);
        }
        try {
            $shipment = $this->_initShipment();
            if (!$shipment) {
                $this->_forward('noRoute');

                return;
            }

            $shipment->register();
            $comment = '';
            if (!empty($data['comment_text'])) {
                $shipment->addComment($data['comment_text'], isset($data['comment_customer_notify']), isset($data['is_visible_on_front']));
                if (isset($data['comment_customer_notify'])) {
                    $comment = $data['comment_text'];
                }
            }

            if (!empty($data['send_email'])) {
                $shipment->setEmailSent(true);
            }

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
            $responseAjax = new Varien_Object();
            $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];

            if ($isNeedCreateLabel && $this->_createShippingLabel($shipment)) {
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);

            $shipment->sendEmail(!empty($data['send_email']), $comment);

            $shipmentCreatedMessage = $this->__('The shipment has been created.');
            $labelCreatedMessage = $this->__('The shipping label has been created.');

            $this->_getSession()->addSuccess($isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage : $shipmentCreatedMessage);
            Mage::getSingleton('adminhtml/session')->getCommentText(true);
        } catch (Mage_Core_Exception $e) {
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage($e->getMessage());
            } else {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            if ($isNeedCreateLabel) {
                $responseAjax->setError(true);
                $responseAjax->setMessage(Mage::helper('sales')->__('An error occurred while creating shipping label.'));
            } else {
                $this->_getSession()->addError($this->__('Cannot save shipment.'));
                $this->_redirect('adminhtml/sales_order/view', array('order_id' => $this->getRequest()->getParam('order_id')));
            }
        }
        if ($isNeedCreateLabel) {
            $this->getResponse()->setBody($responseAjax->toJson());
        } else {
            $this->_redirect('adminhtml/sales_order/view', array('order_id' => $shipment->getOrderId()));
        }
    }

    /**
     * Create shipping label for specific shipment with validation.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return bool
     */
    protected function _createShippingLabel(Mage_Sales_Model_Order_Shipment $shipment)
    {
        if (!$shipment) {
            return false;
        }

        $despatchbayCarrier = Mage::getModel('webshopapps_despatchbay/carrier_despatchbay');
        $useDespatchbay = false;
        $packages = $this->getRequest()->getParam('packages');

        foreach ($packages as $key => $package) {
            $containerArray = explode('#', $package['params']['container']);
            if (count($containerArray) > 1) {
                $newpackage = $package;
                $newpackage['params']['container'] = $containerArray[1];

                if ($containerArray[0] == $despatchbayCarrier->getCarrierCode()) {
                    $useDespatchbay = true;
                }

                $packages[$key] = $newpackage;
            }
        }

        if (!$useDespatchbay) {
            return parent::_createShippingLabel($shipment);
        }

        $this->getRequest()->setParam('packages', $packages);

        if ($useDespatchbay) {
            $carrier = $despatchbayCarrier;
        } else {
            $carrier = $shipment->getOrder()->getShippingCarrier();
        }

        if (!$carrier->isShippingLabelsAvailable()) {
            return false;
        }

        $shipment->setPackages($this->getRequest()->getParam('packages'));
        $response = Mage::getModel('webshopapps_despatchbay/shipping_shipping')->requestToShipment($shipment);

        if ($response->hasErrors()) {
            Mage::throwException($response->getErrors());
        }

        if (!$response->hasInfo()) {
            return false;
        }

        $labelsContent = array();
        $trackingNumbers = array();
        $info = $response->getInfo();

        foreach ($info as $inf) {
            if (!empty($inf['tracking_number']) && !empty($inf['label_content'])) {
                $labelsContent[] = $inf['label_content'];
                $trackingNumbers[] = $inf['tracking_number'];
            }
        }

        $outputPdf = $this->_combineLabelsPdf($labelsContent);
        $shipment->setShippingLabel($outputPdf->render());

        $carrierCode = $carrier->getCarrierCode();
        $carrierTitle = Mage::getStoreConfig('carriers/' . $carrierCode . '/title', $shipment->getStoreId());

        if ($trackingNumbers) {
            foreach ($trackingNumbers as $trackingNumber) {
                $track = Mage::getModel('sales/order_shipment_track')->setNumber($trackingNumber)->setCarrierCode($carrierCode)->setTitle($carrierTitle);
                $shipment->addTrack($track);
            }
        }

        return true;
    }
}