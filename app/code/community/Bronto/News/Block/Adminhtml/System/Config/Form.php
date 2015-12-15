<?php

class Bronto_News_Block_Adminhtml_System_Config_Form extends Mage_Adminhtml_Block_System_Config_Form
{

    /**
     * Gets the internal url for submission
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('*/announcement/refresh');
    }

    /**
     * @see parent
     */
    public function setParentBlock(Mage_Core_Block_Abstract $block)
    {
        $block
            ->getChild('save_button')
            ->setLabel(Mage::helper('adminhtml')->__('Refresh'))
            ->setOnClick("configForm.submit('{$this->getSubmitUrl()}');");

        return parent::setParentBlock($block);
    }
}
