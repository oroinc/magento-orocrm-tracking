<?php
/**
 * Oro Inc.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is published at http://opensource.org/licenses/osl-3.0.php.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magecore.com so we can send you a copy immediately
 *
 * @category  Oro
 * @package   Tracking
 * @copyright Copyright 2013 Oro Inc. (http://www.orocrm.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Oro_Tracking_Model_Observer
{
    /**
     * Add order ids into tracking block to render on order place success page
     *
     * @param Varien_Event_Observer $observer
     */
    public function onOrderSuccessPageView(Varien_Event_Observer $observer)
    {
        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return;
        }

        $block = Mage::app()->getLayout()->getBlock('oro_tracking');
        if ($block) {
            $block->setOrderIds($orderIds);
        }
    }

    /**
     * Set flag to session that user just registered
     */
    public function onRegistrationSuccess()
    {
        $session = Mage::getSingleton('core/session');
        $session->setData('isJustRegistered', true);
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function onOrderPlace(Varien_Event_Observer $observer)
    {
        /** @var $orderInstance Mage_Sales_Model_Order */
        $orderInstance = $observer->getOrder();

        /** @var Mage_Sales_Model_Quote $quote */
        $quote  = Mage::getModel('sales/quote')->load($orderInstance->getQuoteId());

        $method = $quote->getCheckoutMethod(true);
        if ($method === Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            $this->onRegistrationSuccess();
        }
    }

    /**
     * Set product ID to session after product has been added
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCartItemAdded(Varien_Event_Observer $observer)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = $observer->getEvent()->getProduct();

        $session = Mage::getSingleton('checkout/session');
        $session->setData('justAddedProductId', $product->getId());
    }

    public function onCustomerLoggedIn()
    {
        $session = Mage::getSingleton('core/session');
        $session->setData('isJustLoggedIn', true);
    }

    public function onCustomerLoggedOut()
    {
        $coreSession     = Mage::getSingleton('core/session');
        $customerSession = Mage::getSingleton('customer/session');
        $coreSession->setData('isJustLoggedOut', $customerSession->getCustomerId());
    }
}
