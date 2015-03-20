<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Message extends Bronto_Common_Helper_Data
{
    /**
     * Get Bronto Message Object by ID
     *
     * @param      $messageId
     * @param null $storeId
     * @param null $websiteId
     *
     * @return Bronto_Api_Message_Row
     */
    public function getMessageById($messageId, $storeId = null, $websiteId = null)
    {
        if (!is_null($storeId)) {
            $scope   = 'store';
            $scopeId = $storeId;
        } elseif (!is_null($websiteId)) {
            $scope   = 'website';
            $scopeId = $websiteId;
        } else {
            $scope   = 'default';
            $scopeId = 0;
        }

        /* @var $messageObject Bronto_Api_Message */
        $messageObject = $this->getApi(null, $scope, $scopeId)->getMessageObject();

        // Load Message
        try {
            /* @var $message Bronto_Api_Message_Row */
            $message     = $messageObject->createRow();
            $message->id = $messageId;
            $message->read();
        } catch (Exception $e) {
            $this->writeError($e);
        }

        return $message;
    }

    /**
     * @return array
     */
    public function getAllMessageOptions()
    {
        $messageOptions = array();
        foreach (Mage::app()->getWebsites() as $website) {
            foreach ($website->getGroups() as $group) {
                $stores = $group->getStores();
                if (count($stores) == 0) {
                    continue;
                }
                foreach ($stores as $store) {
                    if (Mage::helper('bronto_email')->isEnabled('store', $store->getId())) {
                        $storeMessages  = $this->getMessagesOptionsArray(
                            $store->getId(),
                            $website->getId()
                        );
                        $messageOptions = array_merge($messageOptions, $storeMessages);
                    }
                }
            }
        }

        $existingValues = array();
        foreach ($messageOptions as $key => $option) {
            if (!in_array($option['value'], $existingValues)) {
                $existingValues[] = $option['value'];
            } else {
                unset($messageOptions[$key]);
            }
        }

        return $messageOptions;
    }

    /**
     * Retrieve array of available Bronto Messages
     *
     * @param null  $store
     * @param null  $websiteId
     * @param array $filter
     * @param bool  $withCreateNew
     *
     * @return array
     */
    public function getMessagesOptionsArray($store = null, $websiteId = null, $filter = array(), $withCreateNew = false)
    {
        if (!is_null($store)) {
            $scope   = 'store';
            $scopeId = $store;
        } elseif (!is_null($websiteId)) {
            $scope   = 'website';
            $scopeId = $websiteId;
        } else {
            $scope   = 'default';
            $scopeId = 0;
        }

        /* @var $api Bronto_Api */
        $api = $this->getApi(null, $scope, $scopeId);

        if ($api) {
            /* @var $messageObject Bronto_Api_Message */
            $messageObject = $api->getMessageObject();

            $options    = array();
            $pageNumber = 1;

            try {
                while ($messages = $messageObject->readAll($filter, false, $pageNumber)) {
                    if ($messages->count() <= 0) {
                        break;
                    }
                    foreach ($messages as $message/* @var $message Bronto_Api_Message_Row */) {
                        if ($message->status == 'active') {
                            $options[] = array(
                                'label' => $message->name,
                                'value' => $message->id,
                            );
                        }
                    }
                    $pageNumber++;
                }
            } catch (Exception $e) {
                Mage::helper('bronto_common')->writeError($e);
            }
        }

        if ($withCreateNew) {
            // Add Create New.. Option
            array_unshift($options, array(
                'label' => '** Create New...',
                'value' => '_new_'
            ));
        } else {
            // Add -- None Selected -- Option
            array_unshift($options, array(
                'label' => '-- None Selected --',
                'value' => ''
            ));
        }


        // Sort Alphabetically
        sort($options);

        return $options;
    }
}
