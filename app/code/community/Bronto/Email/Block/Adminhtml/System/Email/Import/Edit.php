<?php

/**
 * @package     Bronto\Email
 * @copyright   2011-2013 Bronto Software, Inc.
 */
class Bronto_Email_Block_Adminhtml_System_Email_Import_Edit
    extends Mage_Adminhtml_Block_System_Email_Template_Edit
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->setChild('back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(
                    array(
                        'label'   => Mage::helper('adminhtml')->__('Back'),
                        'onclick' => "window.location.href = '" . $this->getUrl('*/*/import') . "'",
                        'class'   => 'back'
                    )
                )
        );
    }
}