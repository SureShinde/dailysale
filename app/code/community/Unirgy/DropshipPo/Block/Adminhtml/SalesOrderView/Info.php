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
 
class Unirgy_DropshipPo_Block_Adminhtml_SalesOrderView_Info extends Mage_Adminhtml_Block_Sales_Order_View_Info
{
    public function getCustomerViewUrl()
    {
        if ($this->getOrder()->getCustomerIsGuest()) {
            return false;
        }
        return $this->getUrl('adminhtml/customer/edit', array('id' => $this->getOrder()->getCustomerId()));
    }

    public function getViewUrl($orderId)
    {
        return $this->getUrl('adminhtml/sales_order/view', array('order_id'=>$orderId));
    }
}