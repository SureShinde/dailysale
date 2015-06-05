<?php
/**
 * Unirgy LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.unirgy.com/LICENSE-M1.txt
 *
 * @category   Unirgy
 * @package    Unirgy_DropshipPo
 * @copyright  Copyright (c) 2008-2009 Unirgy LLC (http://www.unirgy.com)
 * @license    http:///www.unirgy.com/LICENSE-M1.txt
 */
 
class Unirgy_DropshipPo_Block_Adminhtml_Po_View_Tab_Shipments extends Unirgy_Dropship_Block_Adminhtml_Order_Shipments
{
    public function setCollection($collection)
    {
        $collection->addAttributeToFilter('udpo_id', $this->getPo()->getId());
        return parent::setCollection($collection);
    }
    public function getPo()
    {
        return Mage::registry('current_udpo');
    }
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'adminhtml/sales_order_shipment/view',
            array(
                'shipment_id'=> $row->getId(),
                'order_id'  => $row->getOrderId()
             ));
    }
}