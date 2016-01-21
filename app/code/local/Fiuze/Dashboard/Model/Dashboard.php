<?php
class Fiuze_Dashboard_Model_Dashboard extends Mage_Core_Model_Abstract
{
    public function getShipmentsCurrentVendor()
    {
        $vendor = Mage::getSingleton('udropship/session')->getVendor();

        $orders = Mage::getResourceModel('sales/order_collection');
        $orders->getSelect();
        $orders->addAttributeToSelect('entity_id')
            ->addFieldToFilter('status', array('nin' => array('canceled', 'closed', 'complete')));

        $orderIds = explode(",", trim(json_encode(array_keys($orders->getItems())), '[]'));

        $collectionOrderItems = Mage::getModel('sales/order_item')->getCollection();
        $collectionOrders = $collectionOrderItems->addFieldToSelect('*')
            ->addFieldToFilter('order_id', array('in' => $orderIds))
            ->addFieldToFilter('udropship_vendor', $vendor->getId());

        foreach($collectionOrders as $orderItem){

            $fullTrack[] = $orderItem->getData();

        }

        $result=array();
        foreach($fullTrack as $curr_track){

            $date = explode(' ', $curr_track['updated_at']);
            $date = strtotime($date['0']);
            $days = time() - $date;
            $days = $days / 86400;
            $days = (int)$days;
            $result[$days]+=$curr_track['qty_ordered'];

        }

        $f_result=array();
        $count=0;
        foreach ($result as $key => $value) {
            $count+=$value;
            if ($key>10) {
                $f_result['10']+=$value;
            }else {
                $f_result[$key]=$value;
            }
        }
        $f_result['-1']=$count;

        return $f_result;
    }
}