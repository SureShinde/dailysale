<?php
/**
 * DO NOT REMOVE OR MODIFY THIS NOTICE
 *
 * EasyBanner module for Magento - flexible banner management
 *
 * @author Templates-Master Team <www.templates-master.com>
 */

class TM_SegmentationSuite_Model_Mysql4_Segments_Collection extends Mage_CatalogRule_Model_Resource_Rule_Collection
{
    protected function _construct()
    {
        $this->_init('segmentationsuite/segments');
    }
    
    public function addStoreFilter($store, $withAdmin = true)
    {
        $this->getSelect()->join(
            array('store_table' => $this->getTable('prolabels/store')),
            'main_table.rules_id = store_table.rule_id',
            array()
        )
        ->where('store_table.store_id in (?)', $store)
        ->group('main_table.rules_id');
    
        return $this;
    }
}
