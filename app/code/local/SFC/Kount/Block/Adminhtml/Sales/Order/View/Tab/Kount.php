<?php
// @codingStandardsIgnoreStart
/**
 * StoreFront Consulting Kount Magento Extension
 *
 * PHP version 5
 *
 * @category  SFC
 * @package   SFC_Kount
 * @copyright 2009-2015 StoreFront Consulting, Inc. All Rights Reserved.
 *
 */
// @codingStandardsIgnoreEnd

class SFC_Kount_Block_Adminhtml_Sales_Order_View_Tab_Kount extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('kount/sales/order/view/tab/kount.phtml');
    }

    public function getTabLabel()
    {
        return $this->__('Kount');
    }

    public function getTabTitle()
    {
        return $this->__('Kount');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getAdditionalRisInformation()
    {
        $ridAddyInfo = $this->getOrder()->getPayment()->getAdditionalInformation('ris_additional_info');
        if($ridAddyInfo == null) {
            $risInfo = null;
        }
        else {
            $risInfo = get_object_vars(json_decode($ridAddyInfo));
        }
        if(is_array($risInfo)) {
            return $risInfo;
        }
        else {
            return array();
        }
    }

    public function getAWCUrl()
    {
        $risInfo = $this->getAdditionalRisInformation();
        
        if(Mage::getStoreConfig('kount/account/test'))
            $sUrl = 'https://awc.test.kount.net';
        else
            $sUrl = 'https://awc.kount.net';
            
        if (isset($risInfo['TRAN'])) {
            $sUrl .= '/workflow/detail.html?id=' . $risInfo['TRAN'];
        }

        return $sUrl;
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getOrder();
    }
}