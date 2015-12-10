<?php

class Bronto_Product_Block_Adminhtml_System_Recommendation_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    /**
     * Override for immediate action on a recommendation
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $actions = array();
        $actions[] = array(
            'url' => $this->getUrl('*/*/edit', array('id' => $row->getId())),
            'caption' => $this->__('Edit'),
        );
        $actions[] = array(
            'url' => $this->getUrl('*/*/copy', array('id' => $row->getId())),
            'caption' => $this->__('Copy'),
            'confirm' => $this->__('Are you sure you want to copy the selected recommendation?'),
        );
        $actions[] = array(
            'url' => $this->getUrl('*/*/delete', array('id' => $row->getId())),
            'caption' => $this->__('Delete'),
            'confirm' => $this->__('Are you sure you want to delete the selected recommendation?'),
        );
        $actions[] = array(
            'url' => $this->getUrl('*/*/preview', array('entity_id' => $row->getId(), 'ret' => 'index')),
            'caption' => $this->__('Preview'),
        );
        $this->getColumn()->setActions($actions);
        return parent::render($row);
    }
}
