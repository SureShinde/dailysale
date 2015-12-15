<?php

class Unirgy_DropshipMicrosite_Model_Mysql4_CategoryFlat extends Mage_Catalog_Model_Resource_Category_Flat
{
    protected $_uNodes=array();
    protected $_uLoaded=array();
    public function getNodes($parentId, $recursionLevel = 0, $storeId = 0)
    {
        $uKey = '0';
        if (Mage::helper('umicrosite')->useVendorCategoriesFilter()) {
            $uKey = Mage::helper('umicrosite')->getCurrentVendor()->getId();
        }
        if (empty($this->_uLoaded[$uKey])) {
            $selectParent = $this->_getReadAdapter()->select()
                ->from($this->getMainStoreTable($storeId))
                ->where('entity_id = ?', $parentId);
            if ($parentNode = $this->_getReadAdapter()->fetchRow($selectParent)) {
                $parentNode['id'] = $parentNode['entity_id'];
                $parentNode = Mage::getModel('catalog/category')->setData($parentNode);
                $this->_uNodes[$uKey][$parentNode->getId()] = $parentNode;
                $nodes = $this->_loadNodes($parentNode, $recursionLevel, $storeId);
                $childrenItems = array();
                foreach ($nodes as $node) {
                    $pathToParent = explode('/', $node->getPath());
                    array_pop($pathToParent);
                    $pathToParent = implode('/', $pathToParent);
                    $childrenItems[$pathToParent][] = $node;
                }
                $this->addChildNodes($childrenItems, $parentNode->getPath(), $parentNode);
                $childrenNodes = $this->_uNodes[$uKey][$parentNode->getId()];
                if ($childrenNodes->getChildrenNodes()) {
                    $this->_uNodes[$uKey] = $childrenNodes->getChildrenNodes();
                }
                else {
                    $this->_uNodes[$uKey] = array();
                }
                $this->_uLoaded[$uKey] = true;
            }
        }
        return $this->_uNodes[$uKey];
    }
}
