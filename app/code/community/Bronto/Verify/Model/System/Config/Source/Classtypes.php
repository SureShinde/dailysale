<?php

class Bronto_Verify_Model_System_Config_Source_Classtypes
{

    /**
     * @var array
     */
    protected $_options;

    /**
     * Supporting role key => value pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!is_null($this->_options)) {
            return $this->_options;
        }

        $helper         = Mage::helper('bronto_common');
        $this->_options = array(
            'model'      => $helper->__('Model'),
            'helper'     => $helper->__('Helper'),
            'block'      => $helper->__('Block'),
            'controller' => $helper->__('Controller'),
        );

        return $this->_options;
    }
}
