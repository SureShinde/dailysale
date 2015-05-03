<?php

class Unirgy_DropshipMicrosite_Model_Mysql4_CategoryTree extends Mage_Catalog_Model_Resource_Category_Tree
{
    public function __construct()
    {
        parent::__construct();
        if (Mage::helper('umicrosite')->useVendorCategoriesFilter()) {
            $table = Mage::getSingleton('core/resource')->getTableName('catalog/category');
            if (($enableCatIds = Mage::helper('umicrosite')->getVendorEnableCategories())) {
                $a = $this->_select->getAdapter();
                $result = $a->quoteInto($table.'.entity_id in (?)', $enableCatIds);
                foreach ($enableCatIds as $enableCatId) {
                    $result .= ' OR '.$table.'.path like "/'.intval($enableCatId).'/"';
                }
            }
            if (($disableCatIds = Mage::helper('umicrosite')->getVendorDisableCategories())) {
                $a = $this->_select->getAdapter();
                $result = $a->quoteInto($table.'.entity_id not in (?)', $disableCatIds);
                foreach ($disableCatIds as $disableCatId) {
                    $result .= ' AND '.$table.'.path not like "/'.intval($disableCatId).'/"';
                }
            }
            $this->_select->where($result);
        }
    }
}