<?php

abstract class Bronto_Common_Block_Adminhtml_Widget_Button_Abstract extends Mage_Adminhtml_Block_Widget_Button
{

    /**
     * @var Mage_Core_Helper_Data
     */
    protected $_helper;

    /**
     * @return Mage_Core_Helper_Data
     */
    protected function _getHelper()
    {
        if (is_null($this->_helper)) {
            $this->_helper = Mage::helper('bronto_common/support');
        }

        return $this->_helper;
    }

    /**
     * @param Mage_Core_Helper_Data $helper
     *
     * @return Bronto_Common_Block_Adminhtml_Widget_Button_Abstract
     */
    public function setHelper(Mage_Core_Helper_Data $helper)
    {
        $this->_helper = $helper;

        return $this;
    }

    /**
     * Children override for button definition
     */
    protected abstract function _setUp();

    /**
     * Define the button
     */
    protected function _construct()
    {
        $this->setDisabled(!$this->_getHelper()->isRegistered());
        $this->_setup();
    }
}
