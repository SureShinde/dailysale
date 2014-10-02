<?php

class TM_NewsletterBooster_Block_Adminhtml_Template extends Mage_Adminhtml_Block_Template
{
    /**
     * Set transactional emails grid template
     *
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('newsletterbooster/email/template/list.phtml');
    }

    /**
     * Create add button and grid blocks
     *
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->setChild('add_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('newsletterbooster')->__('Create Campaign'),
                    'onclick'   => "window.location='" . $this->getCreateUrl() . "'",
                    'class'     => 'add'
        )));
        $this->setChild('grid', $this->getLayout()->createBlock('newsletterbooster/adminhtml_template_grid', 'email.template.grid')
        );
        return parent::_prepareLayout();
    }

    /**
     * Get URL for create new email template
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('*/*/new');
    }

    /**
     * Get transactional emails page header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        return Mage::helper('newsletterbooster')->__('Campaigns');
    }

    /**
     * Get Add New Template button html
     *
     * @return string
     */
    protected function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
}
