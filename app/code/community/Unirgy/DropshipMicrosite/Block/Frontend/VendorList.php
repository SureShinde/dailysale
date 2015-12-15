<?php

class Unirgy_DropshipMicrosite_Block_Frontend_VendorList extends Mage_Core_Block_Template
{
    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        if ($toolbar = $this->getLayout()->getBlock('umicrosite_list.toolbar')) {
            $toolbar->setCollection($this->getVendorsCollection());
            $this->setChild('toolbar', $toolbar);
        }

        return $this;
    }
    protected $_vendorCollection;
    public function getVendorsCollection()
    {
        if (null === $this->_vendorCollection) {
            $this->_vendorCollection = Mage::getModel('udropship/vendor')->getCollection()->addStatusFilter('A');
        }
        return $this->_vendorCollection;
    }
    public function getSize()
    {
        return $this->getVendorsCollection()->getSize();
    }
}