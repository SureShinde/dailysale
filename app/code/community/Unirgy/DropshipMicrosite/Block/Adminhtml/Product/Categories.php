<?php

class Unirgy_DropshipMicrosite_Block_Adminhtml_Product_Categories
    extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Categories
{
    public function isVendorEnabled($cId=null)
    {
        $flag = !($v = Mage::helper('umicrosite')->getCurrentVendor()) || !$v->getIsLimitCategories();
        if (!$flag && !is_null($cId)) {
            if ($v->getIsLimitCategories() == 1) {
                $flag = in_array($cId, $this->getVendorCategoryIds());
            } elseif ($v->getIsLimitCategories() == 2) {
                $flag = !in_array($cId, $this->getVendorCategoryIds());
            }
        }
        return $flag;
    }
    protected $_vendorCatIds;
    public function getVendorCategoryIds()
    {
        if (is_null($this->_vendorCatIds)) {
            $this->_vendorCatIds = array();
            if (($v = Mage::helper('umicrosite')->getCurrentVendor()) && $v->getIsLimitCategories()) {
                $this->_vendorCatIds = explode(',', implode(',', (array)$v->getLimitCategories()));
            }
        }
        return $this->_vendorCatIds;
    }
    public function getRoot($parentNodeCategory=null, $recursionLevel=3)
    {
        $root = parent::getRoot($parentNodeCategory, $recursionLevel);
        if (!$this->isVendorEnabled($root->getId())) {
            $root->setDisabled(true);
        }
        return $root;
    }
    protected function _getNodeJson($node, $level=1)
    {
        $item = parent::_getNodeJson($node, $level);
        if (!$this->isVendorEnabled($item['id'])) {
            $item['disabled'] = true;
        }
        return $item;
    }
}