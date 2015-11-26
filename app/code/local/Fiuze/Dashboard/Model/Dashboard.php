<?php
class Fiuze_Dashboard_Model_Dashboard extends Mage_Core_Model_Abstract
{
    public function getShipmentsCurrentVendor()
    {
        $vendor = Mage::getSingleton('udropship/session')->getVendor();
        $collection = Mage::getModel('sales/order_shipment')->getCollection();
        $sqlMap = array();
        if (!Mage::helper('udropship')->isSalesFlat()) {
            $collection
                ->addAttributeToSelect(array('order_id', 'total_qty', 'udropship_status', 'udropship_method', 'udropship_method_description'))
                ->joinAttribute('order_increment_id', 'order/increment_id', 'order_id')
                ->joinAttribute('order_created_at', 'order/created_at', 'order_id')
                ->joinAttribute('shipping_method', 'order/shipping_method', 'order_id');
        } else {
            $orderTableQted = $collection->getResource()->getReadConnection()->quoteIdentifier('sales/order');
            $sqlMap['order_increment_id'] = "$orderTableQted.increment_id";
            $sqlMap['order_created_at']   = "$orderTableQted.created_at";
            $collection->join('sales/order', "$orderTableQted.entity_id=main_table.order_id", array(
                'order_increment_id' => 'increment_id',
                'order_created_at' => 'created_at',
                'shipping_method',
            ));
        }

        $collectionShipments = $collection->addAttributeToFilter('udropship_vendor', $vendor->getId());

        foreach($collectionShipments as $shipment){

            $fullTrack[] = $shipment->getData();

        }
        $result=array();
        foreach($fullTrack as $curr_track){

            $date = explode(' ', $curr_track['updated_at']);
            $date = strtotime($date['0']);
            $days = time() - $date;
            $days = $days / 86400;
            $days = (int)$days;
            $result[$days]+=$curr_track['total_qty'];

        }

        $f_result=array();
        $count=0;
        foreach($result as $key => $value){
            $count+=$value;
            if($key>10){
                $f_result['10']+=$value;
            }else{
                $f_result[$key]=$value;
            }
        }
        $f_result['-1']=$count;
        return $f_result;
    }
}