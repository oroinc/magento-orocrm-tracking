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

/**
 * @method array getOrderIds()
 * @method void  setOrderIds(array $orderIds)
 */
class Oro_Tracking_Block_Tracking extends Mage_Core_Block_Template
{
    /**
     * Returns user identifier
     *
     * @return string
     */
    protected function _getUserIdentifier()
    {
        $session = Mage::getModel('customer/session');

        $data = array('id' => null, 'email' => null, 'visitor-id' => Mage::getSingleton('log/visitor')->getId());
        if ($session->isLoggedIn()) {
            $customer = $session->getCustomer();
            $data     = array_merge(
                $data,
                array(
                    'id'    => $customer->getId(),
                    'email' => $customer->getEmail()
                )
            );
        } else {
            $data['id'] = Oro_Tracking_Helper_Data::GUEST_USER_IDENTIFIER;
        }

        return urldecode(http_build_query($data, '', '; '));
    }

    /**
     * Render information about specified orders
     *
     * @return string
     */
    protected function _getOrderEventsData()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return '';
        }

        $collection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToFilter('entity_id', array('in' => $orderIds));

        $result = array();
        /** @var $order Mage_Sales_Model_Order */
        foreach ($collection as $order) {
            $result[] = sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%f' ]);",
                Oro_Tracking_Helper_Data::EVENT_ORDER_PLACE_SUCCESS,
                $order->getSubtotal()
            );
        }

        return implode("\n", $result);
    }

    /**
     * Render information about cart on checkout index page
     *
     * @return string
     */
    protected function _getCheckoutEventsData()
    {
        /** @var $action Mage_Core_Controller_Varien_Action */
        $action          = Mage::app()->getFrontController()->getAction();
        $fullActionName  = $action->getFullActionName();
        $isCheckoutIndex = in_array(
            $fullActionName,
            array('checkout_onepage_index', 'checkout_multishipping_addresses')
        );

        if ($isCheckoutIndex) {
            /** @var $quote Mage_Sales_Model_Quote */
            $quote = Mage::getModel('checkout/session')->getQuote();

            return sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%f' ]);",
                Oro_Tracking_Helper_Data::EVENT_CHECKOUT_STARTED,
                $quote->getSubtotal()
            );
        }

        return '';
    }

    /**
     * Render information about cart items added
     *
     * @return string
     */
    protected function _getCartEventsData()
    {
        $result = array();
        $session = Mage::getSingleton('checkout/session');

        if ($session->hasData('justAddedProductId')) {
            $productId = $session->getData('justAddedProductId');
            $session->unsetData('justAddedProductId');

            $result[] = sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%d' ]);",
                Oro_Tracking_Helper_Data::EVENT_CART_ITEM_ADDED,
                $productId
            );

            $msgToWrite['event'] = Oro_Tracking_Helper_Data::EVENT_CART_ITEM_ADDED;

            if ($session->hasData('justAddedProductName')) {
                $productName = $session->getData('justAddedProductName');
                $session->unsetData('justAddedProductName');

                $msgToWrite['product_name'] = sprintf("Name: %s", $productName);
            }

            if ($session->hasData('justAddedProductBrandName')) {
                $brandName = $session->getData('justAddedProductBrandName');
                $session->unsetData('justAddedProductBrandName');

                $msgToWrite['brand'] = sprintf("Brand: %s", $brandName);
            }

            if ($session->hasData('justAddedProductCategoryIds')) {
                $categoryNames = array();
                $categoryIds = $session->getData('justAddedProductCategoryIds');
                $session->unsetData('justAddedProductCategoryIds');

                foreach ($categoryIds as $categoryId) {
                    $category = Mage::getModel('catalog/category');
                    $category->load($categoryId);
                    $categoryNames[] = $category->getName();
                }
            }

            $categoryNamesString = implode(', ', $categoryNames);
            $productInfo = sprintf("%s, Category:[%s]", implode(', ', $msgToWrite), $categoryNamesString);

            $result[] = sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', '%s' ]); ",
                substr($productInfo, 0, 255),
                $productId
            );
        }

        return implode(PHP_EOL, $result);
    }

    /**
     * Renders information about event on register success
     *
     * @return string
     */
    protected function _getCustomerEventsData()
    {
        $session = Mage::getSingleton('core/session');

        if ($session->getData('isJustRegistered')) {
            $session->unsetData('isJustRegistered');

            return sprintf(
                "_paq.push(['trackEvent', 'OroCRM', 'Tracking', '%s', 1 ]);",
                Oro_Tracking_Helper_Data::EVENT_REGISTRATION_FINISHED
            );
        }

        return '';
    }

    /**
     * Render tracking scripts
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::helper('oro_tracking')->isEnabled()) {
            return '';
        }

        try {
            return parent::_toHtml();
        } catch (LogicException $e) {
            Mage::logException($e);

            return '';
        }
    }
}
