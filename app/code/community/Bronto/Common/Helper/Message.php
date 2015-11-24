<?php

/**
 * @package   Bronto\Common
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Common_Helper_Message extends Bronto_Common_Helper_Data
{
    protected static $_options = array();

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
        $messageObject = $this->getApi(null, $scope, $scopeId)->transferMessage();

        // Load Message
        try {
            return $messageObject->read()
                ->where->id->is($messageId)
                ->withIncludeContent(false)
                ->first();
        } catch (Exception $e) {
            $this->writeError($e);
        }

        return $messageObject->createObject();
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
        $options = array();
        if ($api && !array_key_exists($api->getToken(), self::$_options)) {
            /* @var $messageObject Bronto_Api_Message */
            $messageObject = $api->transferMessage();
            $readMessages = $messageObject->read(array('filter' => $filter))
                ->withIncludeContent(false)
                ->withStatus('active');

            try {
                foreach ($readMessages as $message) {
                    $options[] = array(
                        'label' => $message->getName(),
                        'value' => $message->getId()
                    );
                }
            } catch (Exception $e) {
                Mage::helper('bronto_common')->writeError($e);
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
            self::$_options[$api->getToken()] = $options;
        } else {
            $options = self::$_options[$api->getToken()];
        }

        return $options;
    }
}
