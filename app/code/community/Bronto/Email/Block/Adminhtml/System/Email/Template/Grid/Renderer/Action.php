<?php

class Bronto_Email_Block_Adminhtml_System_Email_Template_Grid_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    public function render(Varien_Object $row)
    {
        $sendType = $row->getTemplateSendType();
        $actions  = array();

        // Edit Action
        $actions[] = array(
            'url'     => $this->getUrl('*/*/brontoEdit', array('id' => $row->getId())),
            'caption' => $this->__('Edit'),
        );

        // Delete Action
        $actions[] = array(
            'url'     => $this->getUrl('*/*/massDelete', array('template_id' => $row->getId())),
            'caption' => $this->__('Delete'),
            'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to delete the selected template?  If this template is currently assigned to be used, it will automatically be reassigned to the default Magento template.')
        );

        switch ($sendType) {
            case 'transactional':
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'marketing')),
                    'caption' => $this->__('Set to Bronto Marketing'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Bronto as a marketing message?'),
                );
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'magento')),
                    'caption' => $this->__('Set to Magento Email'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Magento?'),
                );
                break;
            case 'magento':
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'transactional')),
                    'caption' => $this->__('Set to Bronto Transactional'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Bronto as a transactional message?'),
                );
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'marketing')),
                    'caption' => $this->__('Set to Bronto Marketing'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Bronto as a marketing message?'),
                );
                break;
            default:
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'transactional')),
                    'caption' => $this->__('Set to Bronto Transactional'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Bronto as a transactional message?'),
                );
                $actions[] = array(
                    'url'     => $this->getUrl('*/*/updateSendType', array('template_id' => $row->getId(), 'send_type' => 'magento')),
                    'caption' => $this->__('Set to Magento Email'),
                    'confirm' => Mage::helper('bronto_email')->__('Are you sure you want to set this template to send through Magento?'),
                );
                break;
        }

        $this->getColumn()->setActions($actions);

        return parent::render($row);
    }
}
