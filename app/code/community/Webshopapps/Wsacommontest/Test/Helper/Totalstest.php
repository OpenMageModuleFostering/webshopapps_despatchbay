<?php
/**
 * Created by JetBrains PhpStorm.
 * User: karen
 * Date: 14/02/2013
 * Time: 22:16
 * To change this template use File | Settings | File Templates.
 */

class Webshopapps_Wsacommontest_Test_Helper_Totalstest extends EcomDev_PHPUnit_Test_Case {


    /**
     * Helper instance for test
     *
     * @var Webshopapps_Wsacommon_Helper_Totals
     */
    protected $_helper = null;


    protected $_quote = null;

    protected $_quoteItem = null;

    protected function setUp() {


        $customer = $this->getModelMockBuilder('customer/session')
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        // All calls to Mage::getSingleton for customer/session will now return the mock object
        // Instead of the real one!
        $this->replaceByMock('singleton','customer/session',$customer);

        $this->_quote = Mage::getModel('sales/quote')->load(1);

        // mock this model, and this method within the model
        $this->_quoteItem = $this->getModelMock('sales/quote_item',array('getQuote'));

        // when method getQuote is called then return $this->_quote
        $this->_quoteItem->expects($this->any())
            ->method('getQuote')
            ->will($this->returnValue($this->_quote));

        $this->replaceByMock('singleton','sales/quote_item',$this->_quoteItem);

        $this->app()->getRequest()->setBaseUrl('http://www.localhost.com');
        $_SESSION = array();

        $this->_helper = Mage::helper('wsacommon/totals');


    }

    /**
     * @loadFixture config
     */
    public function testSimpleTotals() {

        $item1 = $this->_quoteItem->load(1);


        $totals = $this->_helper->getTotals($item1);

        $this->assertEquals($totals->getData('weight'),1);
        $this->assertEquals($totals->getData('price'),2);
        $this->assertEquals($totals->getData('qty'),3);

    }
}