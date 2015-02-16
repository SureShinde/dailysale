<?php
/**
 * Observer
 *
 * @category    Fiuze
 * @package     Fiuze_Deals
 * @author      Webinse Team <info@webinse.com>
 */

class Fiuze_Setup_Model_Observer{

    /**
     * Add collumn orders email
     * @param Varien_Event_Observer $observer
     */
    public function appendCustomColumn(Varien_Event_Observer $observer)
    {
        $block = $observer->getBlock();
        if (!isset($block)) {
            return $this;
        }
        if ($block->getType() == 'adminhtml/sales_order_grid') {
            $block->addColumnAfter('customer_group_id', array(
                'header' => Mage::helper('fiuze_deals')->__('Email'),
                'index' => 'customer_email',
                'filter_index' => 'sales_flat_order.customer_email',
                'type' => 'text',
            ), 'shipping_name');
        }
    }

    /**
     * Add collumn orders email collection
     * @param Varien_Event_Observer $observer
     */
    public function salesOrderGridCollectionLoadBefore(Varien_Event_Observer $observer)
    {
        $collection = $observer->getOrderGridCollection();
        $collection->addFilterToMap('store_id', 'main_table.store_id');
        $select = $collection->getSelect();
        $select->join('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id','customer_email');
    }
}