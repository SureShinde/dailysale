<?php
/**
 * @author Fiuze Team
 * @category Fiuze
 * @package Fiuze_Hideshipments
 * @copyright Copyright (c) 2016 Fiuze
 */

class Fiuze_Hideshipments_Model_Observer
{
    public function hideShipment(Varien_Event_Observer $observer)
    {

        $collection = $observer->getOrderShipmentGridCollection();
        if ($collection->getModelName() == "sales/order_shipment") {
            $collection->addFieldToFilter('udropship_status',array('neq'=>'0'));
        }
    }
}