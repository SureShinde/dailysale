<?php

/**
 * @package   Newsletter
 * @copyright 2011-2012 Bronto Software, Inc.
 */
class Bronto_Newsletter_Helper_Contact extends Bronto_Common_Helper_Contact
{

    /**
     * Description for const
     */
    const XML_PATH_UPDATE_STATUS = 'bronto_newsletter/contacts/update_status';
    const XML_PATH_UPDATE_UNSUB  = 'bronto_newsletter/contacts/update_unsub';

    /**
     * Description for const
     */
    const XML_PATH_LISTS = 'bronto_newsletter/contacts/lists';

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return bool
     */
    public function getUpdateStatus($scope = 'default', $scopeId = 0)
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_UPDATE_STATUS, $scope, $scopeId);
    }

    /**
     * @param string $scope
     * @param mixed $scopeId
     * @return bool
     */
    public function isRemoveUnsubs($scope = 'default', $scopeId = 0)
    {
        return (bool) $this->getAdminScopedConfig(self::XML_PATH_UPDATE_UNSUB, $scope, $scopeId);
    }

    /**
     * Get Array of Bronto Subscriber List IDs
     *
     * @param string $scope
     * @param mixed $scopeId
     * @return array|mixed
     */
    public function getListIds($scope = 'default', $scopeId = 0)
    {
        $listIds = $this->getAdminScopedConfig(self::XML_PATH_LISTS, $scope, $scopeId);
        if (empty($listIds)) {
            return array();
        }

        if (!is_array($listIds)) {
            $listIds = explode(',', $listIds);
        }

        return $listIds;
    }

    /**
     * Get the list object from list id
     *
     * @param int   $listId
     * @param string $scope
     * @param mixed $scopeId
     *
     * @return boolean|Bronto_Api_List_Row
     */
    public function getListData($listId, $scope = 'default', $scopeId = 0)
    {
        if ($api = $this->getApi(null, $scope, $scopeId)) {
            /* @var $listObject Bronto_Api_List */
            $listObject = $api->getListObject();
            try {
                foreach ($listObject->readAll(array('id' => $listId))->iterate() as $list/* @var $list Bronto_Api_List_Row */) {
                    if ($list->id == $listId) {
                        return $list;
                    }
                }
            } catch (Exception $e) {
                Mage::helper('bronto_newsletter')->writeError('Failed to retrieve list: ' . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Retrieve helper module name
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return 'Bronto_Newsletter';
    }

    /**
     * Convert Magento Newsletter Subscriber Status to Bronto API Contact Status
     *
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     *
     * @return boolean
     */
    public function getQueueStatus(Mage_Newsletter_Model_Subscriber $subscriber)
    {
        // Set correct status based on subscriber status
        switch ($subscriber->getStatus()) {
            case Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED:
                $status = Bronto_Api_Contact::STATUS_ACTIVE;
                break;

            case Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED:
                $status = Bronto_Api_Contact::STATUS_UNSUBSCRIBED;
                break;

            case Mage_Newsletter_Model_Subscriber::STATUS_UNCONFIRMED:
                $status = Bronto_Api_Contact::STATUS_UNCONFIRMED;
                break;

            case Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE:
            default:
                $status = Bronto_Api_Contact::STATUS_TRANSACTIONAL;
                break;
        }

        return $status;
    }
}
