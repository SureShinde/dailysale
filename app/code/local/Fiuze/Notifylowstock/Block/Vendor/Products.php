<?php

class Fiuze_Notifylowstock_Block_Vendor_Products extends Unirgy_DropshipVendorProduct_Block_Vendor_Products
{
    public function getProductCollection()
    {
        if (!$this->_collection) {
            $v = Mage::getSingleton('udropship/session')->getVendor();
            if (!$v || !$v->getId()) {
                return array();
            }
            $r = Mage::app()->getRequest();
            $res = Mage::getSingleton('core/resource');
            #$read = $res->getConnection('catalog_product');
            $stockTable = $res->getTableName('cataloginventory/stock_item');
            $stockStatusTable = $res->getTableName('cataloginventory/stock_status');
            $wId = (int)Mage::app()->getDefaultStoreView()->getWebsiteId();
            $collection = Mage::getResourceModel('udprod/product_collection')
                ->setFlag('has_group_entity', 1)
                ->addAttributeToFilter('type_id', array('in'=>array('simple','configurable','downloadable','virtual')))
                ->addAttributeToSelect(array('sku', 'name', 'status', 'price', 'cost', 'fiuze_lowstock_flag', 'fiuze_lowstock_qty'))
            ;
            $collection->addAttributeToFilter('entity_id', array('in'=>$v->getAssociatedProductIds()));
            $collection->addAttributeToFilter('visibility', array('in'=>Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds()));
            $conn = $collection->getConnection();
            $wIdsSql = $conn->quote(array_keys(Mage::app()->getWebsites()));
            //$collection->addAttributeToFilter('entity_id', array('in'=>array_keys($v->getAssociatedProducts())));
            $collection->getSelect()
                ->join(
                array('cisi' => $stockTable),
                $conn->quoteInto('cisi.product_id=e.entity_id AND cisi.stock_id=?', Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID),
                    array()
                )
                ->joinLeft(
                    array('ciss' => $stockStatusTable),
                    $conn->quoteInto('ciss.product_id=e.entity_id AND ciss.website_id in ('.$wIdsSql.') AND ciss.stock_id=?', Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID),
                array('_stock_status'=>$this->_getStockField('status'))
            );
            if (Mage::helper('udropship')->isUdmultiAvailable()) {
                $collection->getSelect()->joinLeft(
                    array('uvp' => $res->getTableName('udropship/vendor_product')),
                    $conn->quoteInto('uvp.product_id=e.entity_id AND uvp.vendor_id=?', $v->getId()),
                    array('_stock_qty'=>$this->_getStockField('qty'), 'vendor_sku'=>'uvp.vendor_sku', 'vendor_cost'=>'uvp.vendor_cost')
                );
                //$collection->getSelect()->columns(array('_stock_qty'=>'IFNULL(uvp.stock_qty,cisi.qty'));
            } else {
                if (($vsAttrCode = Mage::getStoreConfig('udropship/vendor/vendor_sku_attribute')) && Mage::helper('udropship')->checkProductAttribute($vsAttrCode)) {
                    $collection->addAttributeToSelect(array($vsAttrCode));
                }
                $collection->getSelect()->columns(array('_stock_qty'=>$this->_getStockField('qty')));
            }
            $collection->addAttributeToFilter('udropship_vendor', $v->getId());

            $this->_applyRequestFilters($collection);

            $collection->getSelect()->group('e.entity_id');
            $collection->getSize();

            #Mage::getModel('cataloginventory/stock')->addItemsToProducts($collection);
            $this->_collection = $collection;
        }
        return $this->_collection;
    }
}