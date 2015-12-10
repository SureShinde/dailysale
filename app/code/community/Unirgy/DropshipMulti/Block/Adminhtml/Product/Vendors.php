<?php

class Unirgy_DropshipMulti_Block_Adminhtml_Product_Vendors
    extends Mage_Adminhtml_Block_Widget
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Initialize block
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setProductId($this->getRequest()->getParam('id'));
        $this->setTemplate('udmulti/product/vendors.phtml');
        $this->setId('udmulti_vendors');
        $this->setUseAjax(true);
    }

    public function getAssociatedVendors()
    {
        $assocVendor = Mage::getModel('udropship/vendor_product')->getCollection()
            ->addProductFilter($this->getProduct()->getId());
        $assocVendor->getSelect()->join(array('uv' => $assocVendor->getTable('udropship/vendor')), 'uv.vendor_id=main_table.vendor_id', array('vendor_name'));
        return $assocVendor;
    }

    /**
     * Check block is readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
         return $this->_getProduct()->getCompositeReadonly();
    }

    /**
     * Retrieve currently edited product object
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * Escape JavaScript string
     *
     * @param string $string
     * @return string
     */
    public function escapeJs($string)
    {
        return addcslashes($string, "'\r\n\\");
    }

    /**
     * Retrieve Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('udmulti')->__('Drop Shipping Vendors');
    }

    /**
     * Retrieve Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('udmulti')->__('Drop Shipping Vendors');
    }

    /**
     * Can show tab flag
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Check is a hidden tab
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
    
    public function getVendorName($vId)
    {
        $v = Mage::helper('udropship')->getVendor($vId);
        return $v && $v->getId() ? $v->getVendorName() : '';
    }
}
