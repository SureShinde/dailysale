<?php

class TM_SegmentationSuite_Helper_Data extends Mage_Core_Helper_Abstract
{
    protected $_customerCount = 25;

    public function getDefaultAddressOptions()
    {
        return array(
            array(
                'value' => 'is_exists',
                'label' => Mage::helper('segmentationsuite')->__('exists')
            ),
            array(
                'value' => 'is_not_exists',
                'label' => Mage::helper('segmentationsuite')->__('does not exist')
            ),
        );
    }

    /**
     * //Reindex for Newsletter unsubscription success
     * @param $segmentId
     * @throws Exception
     */
    public function  reIndexCustomers($segmentId)
    {
        $segmentModel = Mage::getResourceModel('segmentationsuite/segments');
        $segment = Mage::getModel('segmentationsuite/segments');

        $obj = new Varien_Object();
        $segmentModel->deleteSegmentIndex($segmentId);
        $collection = Mage::getModel('customer/customer')->getCollection();
        $storeIds = Mage::app()->getStore()->getId();
        if (!in_array(0, $storeIds)) {
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        }

        $obj->setQueryStep(0);
        $obj->setProcessed(0);
        $obj->setCustomers($collection->getSize());
        $obj->setCustomerCount($this->_customerCount);

        if ($obj->getProcessed() < $obj->getCustomers()) {
            $segment->indexSegment($obj->getCustomerCount(), $obj->getQueryStep(), $segmentId);
           $obj->setQueryStep($obj->getQueryStep() + 1);
            $obj->setProcessed($obj->getProcessed() + $obj->getCustomerCount());
        }
    }
}
