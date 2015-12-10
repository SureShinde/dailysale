<?php

/**
 * @package       Bronto\Common
 * @copyright (c) 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Model_Keysentry extends Mage_Core_Model_Abstract
{
    /**
     * Bronto Common module alias
     */
    const COMMON = 'bronto_common';

    /**
     * Bronto API Retry and Send Queue
     */
    const API = 'bronto_common/api';

    /**
     * Bronto Couponmodue alias
     */
    const COUPON = 'bronto_common/coupon';

    /**
     * Bronto customer module alias
     */
    const CUSTOMER = 'bronto_customer';

    /**
     * Bronto email module alias
     */
    const EMAIL = 'bronto_email';

    /**
     * Bronto newsletter module alias
     */
    const NEWSLETTER = 'bronto_newsletter';

    /**
     * Bronto order module alias
     */
    const ORDER = 'bronto_order';

    /**
     * Bronto reminder module alias
     */
    const REMINDER = 'bronto_reminder';

    /**
     * Bronto reviews module alias
     */
    const REVIEWS = 'bronto_reviews';

    /**
     * Bronto product reccommendations
     */
    const PRODUCT = 'bronto_product';

    /**
     * Disable all the defined modules for the scope
     *
     * @param mixed   $scope          Site scope
     * @param integer $scopeId        Site scope id
     * @param boolean $includeCommon  switch to disable bronto_common module
     * @param boolean $deleteChildren if true will delete config values for child scopes
     */
    public function disableModules($scope, $scopeId, $includeCommon = false, $deleteChildren = false)
    {
        if ($includeCommon) {
            Mage::helper(self::COMMON)->disableModule($scope, $scopeId, $deleteChildren);
        }

        Mage::helper(self::CUSTOMER)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::EMAIL)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::NEWSLETTER)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::ORDER)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::REMINDER)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::REVIEWS)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::PRODUCT)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::COUPON)->disableModule($scope, $scopeId, $deleteChildren);
        Mage::helper(self::API)->disableModule($scope, $scopeId, $deleteChildren);

        Mage::getConfig()->reinit();
        Mage::app()->reinitStores();

        // Get Child Items
        if ('website' == $scope) {
            $website = Mage::app()->getWebsite($scopeId);
            foreach ($website->getStoreIds() as $storeId) {
                $this->disableModules('store', $storeId, $includeCommon, true);
            }
        } elseif ('default' == $scope) {
            foreach (Mage::app()->getWebsites(false) as $website) {
                $this->disableModules('website', $website->getId(), $includeCommon, true);
            }
        }
    }

    /**
     * Remove Bronto Message Connection for Template
     *
     * @param Varien_Data_Collection_Db $collection
     * @param string                    $scope
     * @param string|int                $scopeId
     */
    public function unlinkEmails(Varien_Data_Collection_Db $collection, $scope, $scopeId)
    {
        switch ($scope) {
            case 'stores':
            case 'store':
                $storeId = $scopeId;
                break;
            case 'websites':
            case 'website':
                $storeId = Mage::app()->getWebsite($scopeId)->getStoreIds();
                break;
            default:
                $storeId = false;
                break;
        }

        // create filter
        if ($storeId) {
            if (is_array($storeId)) {
                $filter = array('in' => $storeId);
            } else {
                $filter = array('eq' => $storeId);
            }
            $collection->addFieldToFilter('store_id', $filter);
        }

        // Delete Bronto Message connection to template
        foreach ($collection as $message) {
            $message->delete();
        }
    }
}
