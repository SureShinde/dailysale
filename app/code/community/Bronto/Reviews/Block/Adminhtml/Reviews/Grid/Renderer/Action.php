<?php

class Bronto_Reviews_Block_Adminhtml_Reviews_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * Override for immediate action on a delivery
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $actions = array();
        if ($row->isCancelable()) {
            $actions[] = array(
                'url' => $this->getUrl('*/postpurchase/cancel', array('id' => $row->getId())),
                'caption' => $this->__('Cancel'),
                'confirm' => $this->__('Are you sure you want to cancel the %s for order #%s?', $row->getPostName(), $row->getOrderIncrementId())
            );
        }
        $actions[] = array(
            'url' => $this->getUrl('*/postpurchase/delete', array('id' => $row->getId())),
            'caption' => $this->__('Purge'),
            'confirm' => $this->__('Are you sure you want to purge the %s for order #%s?', $row->getPostName(), $row->getOrderIncrementId())
        );
        $this->getColumn()->setActions($actions);
        return parent::render($row);
    }
}
