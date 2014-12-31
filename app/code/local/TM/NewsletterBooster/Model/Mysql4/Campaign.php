<?php

class TM_NewsletterBooster_Model_Mysql4_Campaign extends Mage_Core_Model_Resource_Email_Template
{
    protected function _construct()
    {
        $this->_init('newsletterbooster/campaign', 'campaign_id');
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        parent::_afterLoad($object);

        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/store'))
            ->where('campaign_id = ?', $object->getId());

        if ($data = $this->_getReadAdapter()->fetchAll($select)) {
            $storesArray = array();
            foreach ($data as $row) {
                $storesArray[] = $row['store_id'];
            }
            $object->setData('stores', $storesArray);
        }

        return $this;
    }

//    public function checkCodeUsage(TM_NewsletterBooster_Model_Campaign $template)
//    {
//        $select = $this->_getReadAdapter()->select()
//            ->from($this->getMainTable(), 'COUNT(*)')
//            ->where('template_code = :template_code');
//        $bind = array(
//            'template_code' => $template->getTemplateCode()
//        );
//
//        $templateId = $template->getCampaignId();
//        if ($templateId) {
//            $select->where('campaign_id != :campaign_id');
//            $bind['campaign_id'] = $templateId;
//        }
//
//        $result = $this->_getReadAdapter()->fetchOne($select, $bind);
//        if ($result) {
//            return true;
//        }
//
//        return false;
//    }

    public function getItemsToProcess($count = 1, $step = 0, $segmentId = null)
    {
        if (null === $segmentId) {
            return array();
        }

        $connection = $this->_getReadAdapter();
        /* Segmentation Suite Customer Ids */
        $segmentSelect = $connection->select()->from(
            array(
                'ssc' => $this->getTable('segmentationsuite/index')
            ), 'entity_id')
            ->order('entity_id')->limit($count, $count * $step);

        $customerIds = $connection->fetchCol($segmentSelect);

        $customerSelect = $connection->select()->from(
            array(
                'cc' => $this->getTable('customer/entity')
            ), 'email')
            ->where('cc.entity_id in (?)', $customerIds);

        $result = $connection -> fetchCol($customerSelect);

        return $result;
    }

    public function getOptionArray($store)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ;

        $rowset = array(null => Mage::helper('newsletterbooster')->__('Please Select'));

        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            if (null === $store) {
                $rowset[$row['campaign_id']] = $row['template_code'];
            } else {
                if ($this->checkCampaignStore($row['campaign_id'], $store)) {
                    $rowset[$row['campaign_id']] = $row['template_code'];
                }
            }
        }

        return $rowset;
    }

    public function getTotalCampaignCount($store)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable());

        $count = 0;
        foreach ($this->_getReadAdapter()->fetchAll($select) as $row) {
            if (null === $store) {
                $count++;
            } else {
                if ($this->checkCampaignStore($row['campaign_id'], $store)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function checkCampaignStore($campaignId, $storeId)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('newsletterbooster/store'))
            ->where('campaign_id = ?', $campaignId)
            ->where('store_id = ?', $storeId);

        if (count($this->_getReadAdapter()->fetchAll($select)) > 0) {
            return true;
        }
        return false;
    }

    public function getFrontendCampaigns()
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('campaign'))
            ->where('in_frontend = 1');

        return $this->_getReadAdapter()->fetchAll($select);
    }
}
