<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Grid_Renderer_Order extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Returns the url to the order view page
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $url = Mage::getModel('adminhtml/url')->getUrl('*/sales_order/view/', array(
            'order_id' => $row->getOrderId()
        ));
        return sprintf('<a href="%s">%s</a>', $url, $row->getOrderIncrementId());
    }
}
