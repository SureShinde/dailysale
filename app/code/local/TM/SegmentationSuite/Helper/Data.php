<?php

class TM_SegmentationSuite_Helper_Data extends Mage_Core_Helper_Abstract
{
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
}
